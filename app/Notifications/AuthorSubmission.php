<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class AuthorSubmission extends Notification
{
    use Queueable;
    public $data;
    public $cover;
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($data,$cover,$manuscript,$supplementary)
    {
        $this->data=$data;
        $this->cover=$cover;
        $this->manuscript=$manuscript;
        $this->supplementary=$supplementary;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        if(!$this->supplementary){
            return (new MailMessage)->from('j.food.stability@gmail.com','Journal Of Food Stability')
            ->subject('Submission Successfully received')->markdown('emails.submit', ['data' => $this->data])
            ->attach($this->cover, [
                'as' =>'cover.'.$this->cover->getClientOriginalExtension(),
                'mime' => $this->cover->getMimeType(),
            ])
            ->attach($this->manuscript, [
                'as' =>'manuscript.'.$this->manuscript->getClientOriginalExtension(),
                'mime' => $this->manuscript->getMimeType(),
            ]);      
        }else{
            return (new MailMessage)->from('j.food.stability@gmail.com','Journal Of Food Stability')
            ->subject('Submission Successfully received')->markdown('emails.submit', ['data' => $this->data])
            ->attach($this->cover, [
                'as' =>'cover.'.$this->cover->getClientOriginalExtension(),
                'mime' => $this->cover->getMimeType(),
            ])
            ->attach($this->manuscript, [
                'as' =>'manuscript.'.$this->manuscript->getClientOriginalExtension(),
                'mime' => $this->manuscript->getMimeType(),
            ])
            ->attach($this->supplementary, [
                'as' =>'supplementary.'.$this->supplementary->getClientOriginalExtension(),
                'mime' => $this->supplementary->getMimeType(),
            ]); 
        }
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
