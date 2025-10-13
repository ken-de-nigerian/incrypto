<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WalletStatusChangeNotification extends Notification
{
    use Queueable;

    protected string $walletKey;
    protected string $status;

    public function __construct(string $walletKey, string $status)
    {
        $this->walletKey = $walletKey;
        $this->status = $status;
    }

    public function via(): array
    {
        return ['database'];
    }

    public function toDatabase(): array
    {
        $action = $this->status === '1' ? 'made visible' : 'hidden';

        return [
            'type' => 'wallet_status_change',
            'wallet_key' => $this->walletKey,
            'title' => 'Wallet Visibility Updated',
            'content' => "The wallet $this->walletKey was successfully $action in your dashboard."
        ];
    }
}
