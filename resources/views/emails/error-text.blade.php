{{ config('app.name', 'Laravel') }} - Error Notification
========================================================

ERROR MESSAGE
-------------
{{ $errorMessage }}

@if($exceptionClass)
{{ __('fin-sentinel::fin-sentinel.error_section_exception') }}
---------------------
Class: {{ $exceptionClass }}
File:  {{ $exceptionFile }}:{{ $exceptionLine }}

@endif
@if($stackTrace)
{{ __('fin-sentinel::fin-sentinel.error_section_trace') }}
-----------
@foreach($stackTrace as $index => $frame)
#{{ $index }} {{ $frame['file'] ?? '?' }}:{{ $frame['line'] ?? '?' }} {{ $frame['class'] ?? '' }}{{ $frame['class'] ? '::' : '' }}{{ $frame['function'] ?? '' }}()
@endforeach

@endif
{{ __('fin-sentinel::fin-sentinel.error_section_request') }}
---------------
@if(isset($requestContext['context']))
Context: {{ $requestContext['context'] }}
Command: {{ $requestContext['command'] }}
@else
URL:    {{ $requestContext['url'] ?? '' }}
Method: {{ $requestContext['method'] ?? '' }}
IP:     {{ $requestContext['ip'] ?? '' }}
@if(!empty($requestContext['params']))
Params: {{ json_encode($requestContext['params'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}
@endif
@if(!empty($requestContext['headers']))
Headers: {{ json_encode($requestContext['headers'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}
@endif
@endif

{{ __('fin-sentinel::fin-sentinel.error_section_user') }}
------------------
Name: {{ $userContext['name'] ?? '' }}
@if(isset($userContext['email']))
Email: {{ $userContext['email'] }}
@endif
@if(isset($userContext['id']))
ID: {{ $userContext['id'] }}
@endif

{{ __('fin-sentinel::fin-sentinel.error_section_environment') }}
-----------
Environment:    {{ $environmentContext['app_env'] ?? '' }}
Debug Mode:     {{ $environmentContext['app_debug'] ? 'Enabled' : 'Disabled' }}
PHP Version:    {{ $environmentContext['php_version'] ?? '' }}
Laravel Version: {{ $environmentContext['laravel_version'] ?? '' }}
Peak Memory:    {{ $environmentContext['memory_peak'] ?? '' }}

---
{{ $environmentContext['timestamp'] ?? '' }}
