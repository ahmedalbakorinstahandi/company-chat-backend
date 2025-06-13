<?php

namespace App\Services;

use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FirebaseNotificationService
{
    /**
     * Send a notification to a specific device.
     *
     * @param string $deviceToken
     * @param string $title
     * @param string $body
     * @param array $data
     * @return void
     */
    public function sendToDevice(string $deviceToken, string $title, string $body, array $data = []): void
    {
        $message = CloudMessage::withTarget('token', $deviceToken)
            ->withNotification(Notification::create($title, $body))
            ->withData($data);

        app('firebase.messaging')->send($message);
    }

    /**
     * Send a notification to multiple devices.
     *
     * @param array $deviceTokens
     * @param string $title
     * @param string $body
     * @param array $data
     * @return void
     */
    public function sendToDevices(array $deviceTokens, string $title, string $body, array $data = []): void
    {
        $message = CloudMessage::new()
            ->withNotification(Notification::create($title, $body))
            ->withData($data);

        app('firebase.messaging')->sendMulticast($message, $deviceTokens);
    }

    /**
     * Send a notification to a topic.
     *
     * @param string $topic
     * @param string $title
     * @param string $body
     * @param array $data
     * @return void
     */
    public function sendToTopic(string $topic, string $title, string $body, array $data = []): void
    {
        $message = CloudMessage::withTarget('topic', $topic)
            ->withNotification(Notification::create($title, $body))
            ->withData($data);

        app('firebase.messaging')->send($message);
    }

    /**
     * Subscribe a device to a topic.
     *
     * @param string $deviceToken
     * @param string $topic
     * @return void
     */
    public function subscribeToTopic(string $deviceToken, string $topic): void
    {
        app('firebase.messaging')->subscribeToTopic($topic, [$deviceToken]);
    }

    /**
     * Unsubscribe a device from a topic.
     *
     * @param string $deviceToken
     * @param string $topic
     * @return void
     */
    public function unsubscribeFromTopic(string $deviceToken, string $topic): void
    {
        app('firebase.messaging')->unsubscribeFromTopic($topic, [$deviceToken]);
    }
} 