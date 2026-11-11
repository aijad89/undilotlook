{{-- One certificate's number, dates and file, shown while $when holds.
     $onFile is the certificate on record: its file is kept unless a new one
     is sent, and only while it is the same certificate. --}}
<div class="evd-join" data-if="{{ $when }}">
    <label class="evd-question" for="{{ $field }}_number">Certificate number</label>
    <input type="text" class="evd-input" id="{{ $field }}_number" name="{{ $field }}_number" maxlength="100"
           value="{{ old($field.'_number', $onFile['number'] ?? '') }}" data-required>
    @error($field.'_number')<p class="evd-msg evd-msg-bad">{{ $message }}</p>@enderror

    <div class="evd-dates">
        <div>
            <label class="evd-question" for="{{ $field }}_start">Valid from</label>
            <input type="date" class="evd-input" id="{{ $field }}_start" name="{{ $field }}_start"
                   value="{{ old($field.'_start', $onFile['start'] ?? '') }}" data-required>
        </div>
        <div>
            <label class="evd-question" for="{{ $field }}_end">Valid until</label>
            <input type="date" class="evd-input" id="{{ $field }}_end" name="{{ $field }}_end" min="{{ now()->toDateString() }}"
                   value="{{ old($field.'_end', $onFile['end'] ?? '') }}" data-required>
        </div>
    </div>
    @error($field.'_start')<p class="evd-msg evd-msg-bad">{{ $message }}</p>@enderror
    @error($field.'_end')<p class="evd-msg evd-msg-bad">{{ $message }}</p>@enderror

    <label class="evd-question" for="{{ $field }}_file">Upload the certificate</label>
    <input type="file" id="{{ $field }}_file" name="{{ $field }}_file" accept=".pdf,.jpg,.jpeg,.png" @if(empty($onFile['path'])) data-required @endif>
    @if(! empty($onFile['path']))
        <p class="evd-note" style="margin-top:0;">On file: {{ $onFile['original'] }}. Leave empty to keep it; a renewed certificate needs its new file.</p>
    @endif
    @error($field.'_file')<p class="evd-msg evd-msg-bad">{{ $message }}</p>@enderror
</div>
