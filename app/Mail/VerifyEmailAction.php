<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerifyEmailAction extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * ユーザーインスタンスや認証URLなど
     *
     * @var mixed
     */
    public $user;
    public $verificationUrl;

    /**
     * 新しいメッセージインスタンスの作成
     *
     * @param  mixed  $user
     * @param  string  $verificationUrl
     * @return void
     */
    public function __construct($user, string $verificationUrl)
    {
        $this->user = $user;
        $this->verificationUrl = $verificationUrl;
    }

    /**
     * メールのエンベロープ（件名など）を取得
     *
     * @return Envelope
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '【勤怠管理アプリ】メールアドレスの確認をお願いします',
        );
    }

    /**
     * メールのコンテンツ（ビュー）定義を取得
     *
     * @return Content
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.verify-email', // プレーンテキストやHTMLのテンプレートビュー
        );
    }

    /**
     * 添付ファイルなどを取得
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
