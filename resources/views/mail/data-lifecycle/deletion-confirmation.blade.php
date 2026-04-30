<x-mail::message>
# Confirm account deletion

Hi {{ $subject->name }},

We received a request to delete your account at **{{ config('app.name') }}**. To proceed, confirm this request by clicking the button below.

After confirmation your account enters a {{ $graceDays }}-day grace period. During this time you remain logged in but your account is read-only — you can cancel the deletion or download a GDPR data export at any moment. Once the grace period ends, all personal data is permanently anonymized.

<x-mail::button :url="$confirmUrl">
Confirm deletion
</x-mail::button>

If you did not request this, **cancel immediately** using the link below. No action will be taken if the confirmation link is not used.

<x-mail::button :url="$cancelUrl" color="error">
Cancel this request
</x-mail::button>

The confirmation link is single-use and tied to this request. If it expires, request deletion again from your account settings.

— The {{ config('app.name') }} team
</x-mail::message>
