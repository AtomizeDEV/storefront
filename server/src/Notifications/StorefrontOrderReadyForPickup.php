<?php

namespace Fleetbase\Storefront\Notifications;

use Exception;
use Fleetbase\FleetOps\Models\Order;
use Fleetbase\FleetOps\Support\Utils;
use Fleetbase\Storefront\Models\NotificationChannel;
use Fleetbase\Storefront\Support\Storefront;
// use Fleetbase\FleetOps\Support\Utils;
use Illuminate\Bus\Queueable;
// use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Apn\ApnChannel;
use NotificationChannels\Apn\ApnMessage;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\AndroidConfig;
use NotificationChannels\Fcm\Resources\AndroidFcmOptions;
use NotificationChannels\Fcm\Resources\AndroidNotification;
use NotificationChannels\Fcm\Resources\ApnsConfig;
use NotificationChannels\Fcm\Resources\ApnsFcmOptions;
use Pushok\AuthProvider\Token as PuskOkToken;
use Pushok\Client as PushOkClient;

class StorefrontOrderReadyForPickup extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Order $order)
    {
        $this->order      = $order->setRelations([]);
        $this->storefront = Storefront::findAbout($this->order->getMeta('storefront_id'));
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array
     */
    public function via($notifiable)
    {
        // $channels = ['mail'];
        $channels = [];

        if (!$this->storefront) {
            return $channels;
        }

        $hasApnNotificationChannels = NotificationChannel::where(['owner_uuid' => $this->storefront->uuid, 'scheme' => 'apn'])->count();
        $hasFcmNotificationChannels = NotificationChannel::where(['owner_uuid' => $this->storefront->uuid, 'scheme' => 'android'])->count();

        if ($hasApnNotificationChannels) {
            $channels[] = ApnChannel::class;
        }
        if ($hasFcmNotificationChannels) {
            $channels[] = FcmChannel::class;
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @return MailMessage
     */
    public function toMail($notifiable)
    {
        $message = (new MailMessage())
            ->subject('Your order from ' . $this->storefront->name . ' is ready for pickup!')
            ->line('You can proceed to pickup your order.');

        // $message->action('View Details', Utils::consoleUrl('', ['shift' => 'fleet-ops/orders/view/' . $this->order->public_id]));

        return $message;
    }

    /**
     * Get the firebase cloud message representation of the notification.
     *
     * @return array
     */
    public function toFcm($notifiable)
    {
        $notification = \NotificationChannels\Fcm\Resources\Notification::create()
            ->setTitle('Your order from ' . $this->storefront->name . ' is ready for pickup!')
            ->setBody('You can proceed to pickup your order.');

        $message = FcmMessage::create()
            ->setData(['order' => $this->order->uuid, 'id' => $this->order->public_id, 'type' => 'order_ready'])
            ->setNotification($notification)
            ->setAndroid(
                AndroidConfig::create()
                    ->setFcmOptions(AndroidFcmOptions::create()->setAnalyticsLabel('analytics'))
                    ->setNotification(AndroidNotification::create()->setColor('#4391EA'))
            )->setApns(
                ApnsConfig::create()
                    ->setFcmOptions(ApnsFcmOptions::create()->setAnalyticsLabel('analytics_ios'))
            );

        return $message;
    }

    public function fcmProject($notifiable, $message)
    {
        $about = Storefront::findAbout($this->order->getMeta('storefront_id'));

        if ($this->order->hasMeta('storefront_notification_channel')) {
            $notificationChannel = NotificationChannel::where([
                'owner_uuid' => $about->uuid,
                'app_key'    => $this->order->getMeta('storefront_notification_channel'),
                'scheme'     => 'fcm',
            ])->first();
        } else {
            $notificationChannel = NotificationChannel::where([
                'owner_uuid' => $about->uuid,
                'scheme'     => 'fcm',
            ])->first();
        }

        if (!$notificationChannel) {
            return 'app';
        }

        $this->configureFcm($notificationChannel);

        return $notificationChannel->app_key;
    }

    public function configureFcm($notificationChannel)
    {
        $config    = (array) $notificationChannel->config;
        $fcmConfig = config('firebase.projects.app');

        // set credentials
        Utils::set($fcmConfig, 'credentials.file', $config['firebase_credentials_json']);

        // set db url
        Utils::set($fcmConfig, 'database.url', $config['firebase_database_url']);

        config('firebase.projects.' . $notificationChannel->app_key, $fcmConfig);

        return $fcmConfig;
    }

    /**
     * Get the apns message representation of the notification.
     *
     * @return array
     */
    public function toApn($notifiable)
    {
        $about = Storefront::findAbout($this->order->getMeta('storefront_id'));

        if ($this->order->hasMeta('storefront_notification_channel')) {
            $notificationChannel = NotificationChannel::where([
                'owner_uuid' => $about->uuid,
                'app_key'    => $this->order->getMeta('storefront_notification_channel'),
                'scheme'     => 'apn',
            ])->first();
        } else {
            $notificationChannel = NotificationChannel::where([
                'owner_uuid' => $about->uuid,
                'scheme'     => 'apn',
            ])->first();
        }

        $config = (array) $notificationChannel->config;

        $message = ApnMessage::create()
            ->badge(1)
            ->title('Your order from ' . $this->storefront->name . ' is ready for pickup!')
            ->body('You can proceed to pickup your order.')
            ->custom('type', 'order_ready')
            ->custom('order', $this->order->uuid)
            ->custom('id', $this->order->public_id);

        try {
            $channelClient = new PushOkClient(PuskOkToken::create($config));
        } catch (\Exception $e) {
            // report to sentry the exception
            app('sentry')->captureException($e);

            // return the apn message to be sent by fleetbase defaults anyway -- backup
            return;
        }

        $message = $message->via($channelClient);

        return $message;
    }
}
