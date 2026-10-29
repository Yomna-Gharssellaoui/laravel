<div class="grid gap-2 rounded-lg border border-slate-200 p-3 md:grid-cols-12" data-step>
    <input type="text" name="steps[{{ $i }}][stage]" value="{{ $step['stage'] ?? '' }}" list="trace-stages" placeholder="Étape" class="tv-input md:col-span-2" required>
    <input type="text" name="steps[{{ $i }}][location]" value="{{ $step['location'] ?? '' }}" placeholder="Lieu" class="tv-input md:col-span-3">
    <input type="datetime-local" name="steps[{{ $i }}][at]" value="{{ $step['at'] ?? '' }}" class="tv-input md:col-span-3">
    <input type="number" step="any" name="steps[{{ $i }}][lat]" value="{{ $step['lat'] ?? '' }}" placeholder="Lat." class="tv-input md:col-span-1">
    <input type="number" step="any" name="steps[{{ $i }}][lon]" value="{{ $step['lon'] ?? '' }}" placeholder="Lon." class="tv-input md:col-span-2">
    <button type="button" class="text-sm text-red-600 hover:underline md:col-span-1" data-remove-step>Retirer</button>
</div>
