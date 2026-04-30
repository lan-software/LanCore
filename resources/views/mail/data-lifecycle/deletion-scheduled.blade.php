<x-mail::message>
# Your account deletion is scheduled

Hi {{ $subject->name }},

You confirmed the deletion of your account at **{{ config('app.name') }}**.

Your data will be permanently anonymized on **{{ $scheduledFor->isoFormat('LLLL') }}**.

You can cancel at any time before that date. Logging in during the grace period also keeps your right to download a GDPR Article 15 export of your data.

<x-mail::button :url="$cancelUrl" color="error">
Cancel deletion
</x-mail::button>

After the grace period ends:
- Your profile, name, email and address will be permanently scrubbed.
- Records required by law (e.g. payment receipts) will be retained for the legally mandated period and cannot be retrieved by you afterwards.

— The {{ config('app.name') }} team
</x-mail::message>
