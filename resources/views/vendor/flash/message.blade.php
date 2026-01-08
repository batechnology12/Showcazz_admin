@foreach (session('flash_notification', collect())->toArray() as $message)
@if ($message['overlay'])
@include('flash::modal', [
'modalClass' => 'flash-modal',
'title'      => $message['title'],
'body'       => $message['message']
])
@else
<div class="alert
     alert-{{ $message['level'] }}
     {{ $message['important'] ? 'alert-important' : '' }} "
     role="alert"
     >
    @if ($message['important'])
    <button type="button"
            class="close"
            data-bs-dismiss="alert"
            aria-hidden="true"
            >&times;</button>
    @endif
    {!! $message['message'] !!}
    <button type="button"
            class="close"
            data-bs-dismiss="alert"
            aria-hidden="true"
            >&times;</button>
</div>

@endif
@endforeach
{{ session()->forget('flash_notification') }}




<script>
    var elements = document.querySelectorAll('.popmessage, .bgoverlay');

if (elements.length > 0) {
    setTimeout(function() {
        elements.forEach(function(element) {
            element.style.display = 'none';
        });
    }, 5000);
}

</script>