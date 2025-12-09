<?php

namespace App\Mail;

use App\Models\Stream;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewLiveStreamNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $stream;
    public $streamTitle;
    public $streamDescription;
    public $streamerName;
    public $streamUrl;
    public $bannerUrl;
    public $isLive;
    public $scheduledAt;
    public $isFree;
    public $price;
    public $currency;
    public $freeMinutes;

    /**
     * Create a new message instance.
     */
    public function __construct(Stream $stream)
    {
        $this->stream = $stream;
        $this->streamTitle = $stream->title;
        $this->streamDescription = $stream->description;
        $this->streamerName = $stream->user->name ?? 'Connect Inc';
        $this->streamUrl = url('/streams/' . $stream->id);
        $this->bannerUrl = $stream->banner_image_url ?? asset('images/default-stream-banner.png');
        $this->isLive = $stream->status === 'live';
        $this->scheduledAt = $stream->scheduled_at;
        $this->isFree = !$stream->is_paid || ($stream->free_minutes > 0 && !$stream->price);
        $this->price = $stream->price;
        $this->currency = $stream->currency;
        $this->freeMinutes = $stream->free_minutes;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->isLive
            ? "🔴 New Live Stream: {$this->streamTitle}"
            : "📅 Upcoming Stream: {$this->streamTitle}";

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            html: 'emails.new-live-stream',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
