<x-mail::message>
# Account deletion cancelled

Hi {{ $subject->name }},

The pending deletion of your account at **{{ config('app.name') }}** has been cancelled. Your account is fully active again.

If you did not perform this cancellation, contact support immediately.

— The {{ config('app.name') }} team
</x-mail::message>
