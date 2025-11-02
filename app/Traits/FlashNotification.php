<?php

namespace App\Traits;

use Illuminate\Http\RedirectResponse;

trait FlashNotification
{
    protected string|null $redirectTo = null;
    protected array $flashData = [];

    public function notify($type, $message = null, $title = null, $duration = 5000): static
    {
        $this->flashData = [
            $type => $message,
            'title' => $title ?? ucfirst($type),
            'duration' => $duration,
        ];
        return $this;
    }

    public function toRoute($route, $parameters = []): RedirectResponse
    {
        return redirect()->route($route, $parameters)->with($this->flashData);
    }

    public function toBack(): RedirectResponse
    {
        return back()->with($this->flashData);
    }

    public function notifyErrorWithValidation($message, $errors = [], $title = 'Error', $duration = 5000): RedirectResponse
    {
        return back()
            ->withInput()
            ->withErrors($errors)
            ->with([
                'error' => $message,
                'title' => $title,
                'duration' => $duration,
            ]);
    }
}
