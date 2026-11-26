@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
<img src="{{ asset('brand/zr-logo-horizontal-colour.png') }}" width="240" height="62" alt="{{ trim(strip_tags($slot)) }}" style="display: block; height: auto; max-width: 100%; width: 240px; border: 0; outline: none; text-decoration: none;">
</a>
</td>
</tr>
