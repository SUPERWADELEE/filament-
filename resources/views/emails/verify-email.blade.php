@component('mail::message')
# 您好 {{ $user->name }}

請點擊下面的按鈕驗證您的電子郵件地址。

@component('mail::button', ['url' => $verificationUrl])
驗證電子郵件
@endcomponent

如果您沒有創建此帳號，請忽略此郵件。

謝謝,<br>
{{ config('app.name') }}
@endcomponent