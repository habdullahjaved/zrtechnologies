@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
<img src="{{ asset('brand/zr-logo-email-signature-300w.png') }}" width="150" height="105" alt="{{ trim(strip_tags($slot)) }}" style="height: 105px; width: 150px; border: 0;">
</a>
</td>
</tr>
