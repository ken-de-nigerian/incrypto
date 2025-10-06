<?php

namespace App\Mail;

use App\Models\KycSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewKycSubmissionAlert extends Mailable
{
    use Queueable, SerializesModels;

    public KycSubmission $submission;

    public function __construct(KycSubmission $submission)
    {
        $this->submission = $submission;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New KYC Submission for Review',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.kyc.new_submission_alert',
        );
    }
}
