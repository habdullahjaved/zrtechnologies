import math, os, json
from PIL import Image, ImageDraw, ImageFont, ImageFilter

BASE = '/home/claude/zr'
OUT = os.path.join(BASE, 'zr-brand-assets')
D = {k: os.path.join(OUT, k) for k in ['logo', 'favicon', 'og', 'social', 'hero']}
for p in D.values(): os.makedirs(p, exist_ok=True)

NAVY950 = (0, 22, 63); NAVY = (10, 31, 78); ROYAL = (0, 88, 176); BLUE = (30, 111, 217); SKY = (63, 169, 245); ICE = (238, 245, 255); WHITE = (255, 255, 255)
MONT = '/usr/share/fonts/truetype/google-fonts/ofl/montserrat/Montserrat[wght].ttf'
INTER = '/usr/share/fonts/opentype/inter/Inter-{}.otf'

def mont(size, w=800):
    f = ImageFont.truetype(MONT, size)
    try: f.set_variation_by_axes([w])
    except Exception: pass
    return f
def inter(size, w='SemiBold'): return ImageFont.truetype(INTER.format(w), size)

full = Image.open(f'{BASE}/_full.png'); full_light = Image.open(f'{BASE}/_full_light.png')
mark = Image.open(f'{BASE}/_mark.png'); letters = Image.open(f'{BASE}/_letters.png'); wordtext = Image.open(f'{BASE}/_text.png')

def fit(img, w=None, h=None):
    s = img.copy(); s.thumbnail((w or 99999, h or 99999), Image.LANCZOS); return s
def pad_canvas(img, pad, bg=(0, 0, 0, 0)):
    c = Image.new('RGBA', (img.width + 2 * pad, img.height + 2 * pad), bg); c.alpha_composite(img, (pad, pad)); return c

# ---------------- LOGOS ----------------
full.save(f"{D['logo']}/zr-logo-full-colour.png")
fit(full, 600).save(f"{D['logo']}/zr-logo-full-colour-600w.png")
fit(full, 300).save(f"{D['logo']}/zr-logo-email-signature-300w.png")
full_light.save(f"{D['logo']}/zr-logo-full-for-dark-bg.png")
fit(full_light, 600).save(f"{D['logo']}/zr-logo-full-for-dark-bg-600w.png")
mark.save(f"{D['logo']}/zr-mark-colour.png")
fit(mark, 400).save(f"{D['logo']}/zr-mark-colour-400w.png")
# horizontal lockup: mark + "TECHNOLOGIES LTD"
def horizontal(light=False):
    m = fit(mark, h=300)
    t = fit(full_light if light else full, 10)  # placeholder
    txt = wordtext.copy()
    if light:
        import numpy as np
        a = np.asarray(txt).copy().astype(float); dark = a[..., :3].mean(-1) < 90; a[dark, 0:3] = 255
        txt = Image.fromarray(a.astype('uint8'), 'RGBA')
    txt = fit(txt, w=int(m.width * 1.25))
    W = m.width + 40 + txt.width; H = m.height
    c = Image.new('RGBA', (W, H), (0, 0, 0, 0)); c.alpha_composite(m, (0, 0)); c.alpha_composite(txt, (m.width + 40, (H - txt.height) // 2 + 20))
    return c
horizontal().save(f"{D['logo']}/zr-logo-horizontal-colour.png")
horizontal(True).save(f"{D['logo']}/zr-logo-horizontal-for-dark-bg.png")
# white (mono) logo for coloured backgrounds
import numpy as np
a = np.asarray(full).copy(); a[..., 0:3] = 255
Image.fromarray(a, 'RGBA').save(f"{D['logo']}/zr-logo-white-mono.png")

# ---------------- FAVICONS ----------------
def tile(src, size, bg=WHITE + (255,), pad=0.07, radius=0.22, transparent_corners=True):
    ss = 4; S = size * ss
    t = Image.new('RGBA', (S, S), (0, 0, 0, 0)); d = ImageDraw.Draw(t)
    if transparent_corners: d.rounded_rectangle((0, 0, S - 1, S - 1), radius=int(S * radius), fill=bg)
    else: d.rectangle((0, 0, S, S), fill=bg)
    inner = int(S * (1 - 2 * pad)); s = fit(src, inner, inner)
    t.alpha_composite(s, ((S - s.width) // 2, (S - s.height) // 2))
    return t.resize((size, size), Image.LANCZOS)
small = {s: tile(letters, s, pad=0.06) for s in (16, 32, 48)}
for s, im in small.items(): im.save(f"{D['favicon']}/favicon-{s}x{s}.png")
small[48].save(f"{D['favicon']}/favicon.ico", sizes=[(16, 16), (32, 32), (48, 48)], append_images=[small[16], small[32]])
tile(mark, 180, pad=0.08, transparent_corners=False).convert('RGB').save(f"{D['favicon']}/apple-touch-icon.png")
for s in (192, 512): tile(mark, s, pad=0.08).save(f"{D['favicon']}/android-chrome-{s}x{s}.png")
tile(mark, 512, pad=0.18, transparent_corners=False).save(f"{D['favicon']}/maskable-icon-512x512.png")
tile(mark, 150, pad=0.1, transparent_corners=False).save(f"{D['favicon']}/mstile-150x150.png")

manifest = {"name": "ZR Technologies", "short_name": "ZR Tech", "description": "iPhone repair with collection & delivery in Blackburn",
            "start_url": "/", "display": "standalone", "background_color": "#FFFFFF", "theme_color": "#0A1F4E",
            "icons": [{"src": "/android-chrome-192x192.png", "sizes": "192x192", "type": "image/png"},
                      {"src": "/android-chrome-512x512.png", "sizes": "512x512", "type": "image/png"},
                      {"src": "/maskable-icon-512x512.png", "sizes": "512x512", "type": "image/png", "purpose": "maskable"}]}
json.dump(manifest, open(f"{D['favicon']}/site.webmanifest", 'w'), indent=2)
open(f"{D['favicon']}/browserconfig.xml", 'w').write('<?xml version="1.0" encoding="utf-8"?>\n<browserconfig><msapplication><tile><square150x150logo src="/mstile-150x150.png"/><TileColor>#0A1F4E</TileColor></tile></msapplication></browserconfig>\n')

# ---------------- DRAWING HELPERS ----------------
def gradient(w, h, stops, angle=135):
    # stops: list of (pos, rgb)
    ang = math.radians(angle); dx, dy = math.cos(ang), math.sin(ang)
    xs = np.linspace(0, 1, w)[None, :]; ys = np.linspace(0, 1, h)[:, None]
    t = (xs * dx * w + ys * dy * h); t = (t - t.min()) / (t.max() - t.min())
    out = np.zeros((h, w, 3))
    for (p0, c0), (p1, c1) in zip(stops, stops[1:]):
        m = (t >= p0) & (t <= p1); k = ((t - p0) / max(p1 - p0, 1e-6))[..., None]
        out = np.where(m[..., None], np.array(c0) * (1 - k) + np.array(c1) * k, out)
    return Image.fromarray(out.astype('uint8'), 'RGB').convert('RGBA')

BRAND_STOPS = [(0, NAVY950), (0.45, NAVY), (0.78, ROYAL), (1, BLUE)]

def swooshes(img, cx, cy, rx, ry, rot=-12, width=10, color=SKY, alpha=170, arcs=((200, 340), (20, 150))):
    W, H = img.size; L = Image.new('RGBA', (W, H), (0, 0, 0, 0)); d = ImageDraw.Draw(L)
    for a0, a1 in arcs:
        pts = []
        for i in range(120):
            a = math.radians(a0 + (a1 - a0) * i / 119)
            x, y = rx * math.cos(a), ry * math.sin(a); r = math.radians(rot)
            pts.append((cx + x * math.cos(r) - y * math.sin(r), cy + x * math.sin(r) + y * math.cos(r)))
        for i in range(len(pts) - 1):
            f = math.sin(math.pi * i / (len(pts) - 1))  # taper
            d.line([pts[i], pts[i + 1]], fill=color + (int(alpha * f),), width=max(1, int(width * f)))
    glow = L.filter(ImageFilter.GaussianBlur(width * 1.2))
    img.alpha_composite(glow); img.alpha_composite(L)

def halftone(img, x0, y0, cols, rows, step, rmax, color=SKY, alpha=120):
    d = ImageDraw.Draw(img, 'RGBA')
    for i in range(cols):
        for j in range(rows):
            f = 1 - (i / cols) * 0.85
            r = rmax * f
            if r < 0.8: continue
            x, y = x0 + i * step, y0 + j * step
            d.ellipse((x - r, y - r, x + r, y + r), fill=color + (alpha,))

def phone_layer(w, h, icon, back=False):
    """generic smartphone illustration (no brand marks)"""
    L = Image.new('RGBA', (w + 160, h + 160), (0, 0, 0, 0))
    sh = Image.new('RGBA', L.size, (0, 0, 0, 0)); ImageDraw.Draw(sh).rounded_rectangle((90, 100, 80 + w, 90 + h), radius=int(w * 0.16), fill=(0, 8, 30, 160))
    L.alpha_composite(sh.filter(ImageFilter.GaussianBlur(28)))
    d = ImageDraw.Draw(L)
    x0, y0 = 80, 80; R = int(w * 0.16)
    d.rounded_rectangle((x0, y0, x0 + w, y0 + h), radius=R, fill=(28, 36, 58))  # frame
    d.rounded_rectangle((x0 + 4, y0 + 4, x0 + w - 4, y0 + h - 4), radius=R - 4, outline=(120, 140, 175), width=3)
    b = int(w * 0.045)
    scr = (x0 + b, y0 + b, x0 + w - b, y0 + h - b)
    sw, shh = scr[2] - scr[0], scr[3] - scr[1]
    if back:
        g = gradient(sw, shh, [(0, (52, 70, 110)), (1, (22, 34, 64))], 120)
    else:
        g = gradient(sw, shh, [(0, (16, 48, 110)), (0.6, (8, 80, 170)), (1, (60, 160, 240))], 160)
    m = Image.new('L', (sw, shh), 0); ImageDraw.Draw(m).rounded_rectangle((0, 0, sw - 1, shh - 1), radius=R - b, fill=255)
    L.paste(g, (scr[0], scr[1]), m)
    if not back:
        iw = int(sw * 0.32); ih = int(sw * 0.09)
        d.rounded_rectangle((x0 + w // 2 - iw // 2, scr[1] + int(sw * 0.05), x0 + w // 2 + iw // 2, scr[1] + int(sw * 0.05) + ih), radius=ih // 2, fill=(5, 8, 18))
    cx, cy = x0 + w // 2, y0 + h // 2
    draw_icon(L, icon, cx, cy, sw, scr)
    return L

def crack(d, cx, cy, s, seed=3):
    import random; rnd = random.Random(seed)
    for k in range(9):
        a = rnd.uniform(0, 2 * math.pi); x, y = cx, cy; seg = rnd.randint(3, 6)
        for _ in range(seg):
            a += rnd.uniform(-0.5, 0.5); l = rnd.uniform(s * 0.08, s * 0.2)
            nx, ny = x + l * math.cos(a), y + l * math.sin(a)
            d.line([(x, y), (nx, ny)], fill=(255, 255, 255, 220), width=3); x, y = nx, ny
    d.ellipse((cx - 8, cy - 8, cx + 8, cy + 8), fill=(255, 255, 255, 230))

def check_badge(L, x, y, r):
    d = ImageDraw.Draw(L)
    d.ellipse((x - r, y - r, x + r, y + r), fill=(22, 163, 74), outline=WHITE, width=max(3, r // 10))
    d.line([(x - r * 0.45, y + r * 0.02), (x - r * 0.1, y + r * 0.35), (x + r * 0.5, y - r * 0.35)], fill=WHITE, width=max(4, r // 5), joint='curve')

def draw_icon(L, icon, cx, cy, sw, scr):
    d = ImageDraw.Draw(L, 'RGBA'); s = sw; W_ = (255, 255, 255, 235)
    if icon == 'logo':
        m = fit(full_light, int(s * 0.78)); L.alpha_composite(m, (cx - m.width // 2, cy - m.height // 2 - 20))
        check_badge(L, cx + int(s * 0.28), cy + int(s * 0.42), int(s * 0.12))
    elif icon == 'crack':
        crack(d, cx - s * 0.1, cy - s * 0.15, s)
    elif icon == 'shield':
        w = s * 0.42; top = cy - w * 0.7
        pts = [(cx, top), (cx + w / 2, top + w * 0.2), (cx + w / 2, top + w * 0.65), (cx, top + w * 1.25), (cx - w / 2, top + w * 0.65), (cx - w / 2, top + w * 0.2)]
        d.polygon(pts, fill=(255, 255, 255, 40), outline=W_, width=6)
        d.line([(cx - w * 0.2, top + w * 0.6), (cx - w * 0.03, top + w * 0.78), (cx + w * 0.24, top + w * 0.42)], fill=W_, width=10, joint='curve')
    elif icon == 'battery':
        bw, bh = s * 0.34, s * 0.7; x0, y0 = cx - bw / 2, cy - bh / 2 + 10
        d.rounded_rectangle((cx - bw * 0.18, y0 - bh * 0.07, cx + bw * 0.18, y0 + 4), radius=6, fill=W_)
        d.rounded_rectangle((x0, y0, x0 + bw, y0 + bh), radius=18, outline=W_, width=8)
        for i in range(4):
            yy = y0 + bh - 16 - (i + 1) * (bh - 24) / 4
            d.rounded_rectangle((x0 + 16, yy + 4, x0 + bw - 16, yy + (bh - 24) / 4 - 4), radius=6, fill=(74, 222, 128, 235))
        bolt(d, cx, cy + 10, s * 0.22)
    elif icon == 'charging':
        bolt(d, cx, cy - s * 0.12, s * 0.38)
        d.rounded_rectangle((cx - s * 0.1, scr[3] - s * 0.05, cx + s * 0.1, scr[3] + s * 0.12), radius=8, fill=(230, 235, 245))
        d.line([(cx, scr[3] + s * 0.12), (cx, scr[3] + s * 0.45)], fill=(230, 235, 245), width=12)
    elif icon == 'back':
        cs = s * 0.42; x0, y0 = scr[0] + s * 0.08, scr[1] + s * 0.08
        d.rounded_rectangle((x0, y0, x0 + cs, y0 + cs), radius=int(cs * 0.25), fill=(40, 52, 82), outline=(140, 155, 190), width=3)
        for (fx, fy) in [(0.3, 0.3), (0.3, 0.72), (0.72, 0.5)]:
            r = cs * 0.17; X, Y = x0 + cs * fx, y0 + cs * fy
            d.ellipse((X - r, Y - r, X + r, Y + r), fill=(10, 14, 26), outline=(150, 165, 200), width=4)
            d.ellipse((X - r * 0.35, Y - r * 0.35, X + r * 0.1, Y + r * 0.1), fill=(70, 120, 200, 200))
        crack(d, cx + s * 0.05, cy + s * 0.35, s, seed=7)
    elif icon == 'camera':
        r = s * 0.28
        d.ellipse((cx - r, cy - r, cx + r, cy + r), outline=W_, width=10)
        d.ellipse((cx - r * 0.6, cy - r * 0.6, cx + r * 0.6, cy + r * 0.6), fill=(10, 20, 45, 220), outline=W_, width=6)
        d.ellipse((cx - r * 0.3, cy - r * 0.35, cx + 0, cy - r * 0.05), fill=(140, 200, 255, 200))
    elif icon == 'water':
        for (dx, dy, k) in [(0, -0.05, 1), (-0.22, 0.25, 0.6), (0.2, 0.3, 0.5)]:
            drop(d, cx + s * dx, cy + s * dy, s * 0.2 * k)
    elif icon == 'calendar':
        w = s * 0.62; x0, y0 = cx - w / 2, cy - w / 2
        d.rounded_rectangle((x0, y0, x0 + w, y0 + w), radius=20, fill=(255, 255, 255, 235))
        d.rounded_rectangle((x0, y0, x0 + w, y0 + w * 0.25), radius=20, fill=(220, 38, 38)); d.rectangle((x0, y0 + w * 0.15, x0 + w, y0 + w * 0.25), fill=(220, 38, 38))
        for i in range(3):
            for j in range(3):
                X = x0 + w * (0.18 + i * 0.32); Y = y0 + w * (0.42 + j * 0.2)
                d.rounded_rectangle((X - 10, Y - 7, X + 10, Y + 7), radius=3, fill=(180, 195, 220))
        check_badge(L, int(x0 + w), int(y0 + w), int(s * 0.13))
    elif icon == 'price':
        w = s * 0.55
        pts = [(cx - w / 2, cy - w * 0.3), (cx + w * 0.2, cy - w * 0.3), (cx + w / 2, cy), (cx + w * 0.2, cy + w * 0.3), (cx - w / 2, cy + w * 0.3)]
        d.polygon(pts, fill=(255, 255, 255, 240))
        d.ellipse((cx + w * 0.17, cy - 9, cx + w * 0.17 + 18, cy + 9), fill=(10, 60, 140))
        f = mont(int(w * 0.42), 900); d.text((cx - w * 0.1, cy), '£', font=f, fill=NAVY, anchor='mm')
    elif icon == 'pin':
        for k, al in [(0.55, 60), (0.4, 90)]:
            d.ellipse((cx - s * k, cy + s * 0.25 - s * k * 0.3, cx + s * k, cy + s * 0.25 + s * k * 0.3), outline=(255, 255, 255, al), width=4)
        r = s * 0.2; top = cy - s * 0.35
        d.ellipse((cx - r, top, cx + r, top + 2 * r), fill=(239, 68, 68))
        d.polygon([(cx - r * 0.85, top + r * 1.45), (cx + r * 0.85, top + r * 1.45), (cx, cy + s * 0.25)], fill=(239, 68, 68))
        d.ellipse((cx - r * 0.4, top + r * 0.6, cx + r * 0.4, top + r * 1.4), fill=WHITE)
    elif icon == 'van':
        w = s * 0.7; x0, y0 = cx - w / 2, cy - w * 0.2
        d.rounded_rectangle((x0, y0, x0 + w * 0.65, y0 + w * 0.42), radius=10, fill=(255, 255, 255, 240))
        d.polygon([(x0 + w * 0.65, y0 + w * 0.1), (x0 + w * 0.85, y0 + w * 0.1), (x0 + w, y0 + w * 0.26), (x0 + w, y0 + w * 0.42), (x0 + w * 0.65, y0 + w * 0.42)], fill=(255, 255, 255, 240))
        d.polygon([(x0 + w * 0.7, y0 + w * 0.14), (x0 + w * 0.84, y0 + w * 0.14), (x0 + w * 0.94, y0 + w * 0.25), (x0 + w * 0.7, y0 + w * 0.25)], fill=(120, 180, 240))
        for X in (x0 + w * 0.2, x0 + w * 0.8):
            d.ellipse((X - w * 0.09, y0 + w * 0.34, X + w * 0.09, y0 + w * 0.52), fill=(15, 20, 35), outline=(200, 210, 230), width=5)
        L.alpha_composite(fit(mark, int(w * 0.42)), (int(x0 + w * 0.1), int(y0 + w * 0.08)))
        for i, yy in enumerate([cy - s * 0.55, cy - s * 0.42]): pass
    elif icon == 'chat':
        w = s * 0.6; x0, y0 = cx - w / 2, cy - w * 0.45
        d.rounded_rectangle((x0, y0, x0 + w, y0 + w * 0.62), radius=26, fill=(255, 255, 255, 240))
        d.polygon([(x0 + w * 0.2, y0 + w * 0.6), (x0 + w * 0.15, y0 + w * 0.85), (x0 + w * 0.42, y0 + w * 0.6)], fill=(255, 255, 255, 240))
        for i in range(3):
            X = x0 + w * (0.3 + i * 0.2); d.ellipse((X - 11, y0 + w * 0.31 - 11, X + 11, y0 + w * 0.31 + 11), fill=ROYAL)
    elif icon == 'doc':
        w = s * 0.5; x0, y0 = cx - w / 2, cy - w * 0.7
        d.rounded_rectangle((x0, y0, x0 + w, y0 + w * 1.3), radius=16, fill=(255, 255, 255, 240))
        for i in range(6):
            yy = y0 + w * (0.18 + i * 0.16); d.rounded_rectangle((x0 + w * 0.14, yy, x0 + w * (0.86 if i % 3 else 0.6), yy + 9), radius=4, fill=(170, 185, 215) if i else ROYAL)
        check_badge(L, int(x0 + w), int(y0 + w * 1.25), int(s * 0.12))
    elif icon == 'play':
        r = s * 0.27; d.ellipse((cx - r, cy - r, cx + r, cy + r), fill=(255, 255, 255, 235))
        d.polygon([(cx - r * 0.3, cy - r * 0.45), (cx - r * 0.3, cy + r * 0.45), (cx + r * 0.5, cy)], fill=ROYAL)
    elif icon == 'briefcase':
        w = s * 0.62; x0, y0 = cx - w / 2, cy - w * 0.28
        d.rounded_rectangle((cx - w * 0.18, y0 - w * 0.18, cx + w * 0.18, y0 + 6), radius=12, outline=W_, width=8)
        d.rounded_rectangle((x0, y0, x0 + w, y0 + w * 0.66), radius=20, fill=(255, 255, 255, 240))
        d.rectangle((x0, y0 + w * 0.26, x0 + w, y0 + w * 0.3), fill=(170, 185, 215))
        d.rounded_rectangle((cx - 16, y0 + w * 0.2, cx + 16, y0 + w * 0.36), radius=5, fill=ROYAL)
    elif icon == 'search':
        r = s * 0.2; d.ellipse((cx - r - 20, cy - r - 30, cx + r - 20, cy + r - 30), outline=W_, width=12)
        d.line([(cx + r * 0.5, cy + r * 0.4), (cx + r * 1.3, cy + r * 1.3)], fill=W_, width=18)
    elif icon == 'question':
        r = s * 0.28; d.ellipse((cx - r, cy - r, cx + r, cy + r), fill=(255, 255, 255, 235))
        d.text((cx, cy + 4), '?', font=mont(int(r * 1.3), 900), fill=ROYAL, anchor='mm')

def bolt(d, cx, cy, s):
    pts = [(cx + s * 0.1, cy - s * 0.5), (cx - s * 0.3, cy + s * 0.05), (cx - s * 0.02, cy + s * 0.05), (cx - s * 0.12, cy + s * 0.5), (cx + s * 0.3, cy - s * 0.08), (cx + s * 0.02, cy - s * 0.08)]
    d.polygon(pts, fill=(250, 204, 21))

def drop(d, cx, cy, r):
    d.ellipse((cx - r, cy - r * 0.2, cx + r, cy + r * 1.8), fill=(140, 210, 255, 230))
    d.polygon([(cx - r * 0.92, cy + r * 0.45), (cx + r * 0.92, cy + r * 0.45), (cx, cy - r * 1.4)], fill=(140, 210, 255, 230))
    d.ellipse((cx - r * 0.5, cy + r * 0.4, cx - r * 0.15, cy + r * 0.9), fill=(255, 255, 255, 200))

def wrap(d, text, font, maxw):
    words = text.split(); lines = []; cur = ''
    for w in words:
        t = (cur + ' ' + w).strip()
        if d.textlength(t, font=font) <= maxw: cur = t
        else: lines.append(cur); cur = w
    lines.append(cur); return lines

def pill(d, x, y, text, font, fill=(255, 255, 255, 34), outline=(255, 255, 255, 110), color=WHITE, dot=SKY, img=None):
    tw = d.textlength(text, font=font); h = font.size + 22; w = tw + 58
    lay = Image.new('RGBA', img.size, (0, 0, 0, 0)); ImageDraw.Draw(lay).rounded_rectangle((x, y, x + w, y + h), radius=h // 2, fill=fill, outline=outline, width=2); img.alpha_composite(lay)
    d = ImageDraw.Draw(img)
    if False: d.rounded_rectangle((x, y, x + w, y + h), radius=h // 2, fill=fill, outline=outline, width=2)
    d.ellipse((x + 18, y + h / 2 - 6, x + 30, y + h / 2 + 6), fill=dot)
    d.text((x + 40, y + h / 2), text, font=font, fill=color, anchor='lm'); return w

# ---------------- OG IMAGES ----------------
def og_image(slug, eyebrow, title, sub, icon, chips=("Collection & delivery", "Repair warranty", "Fast turnaround"), back=False, blank=False):
    S = 2; W, H = 1200 * S, 630 * S
    img = gradient(W, H, BRAND_STOPS, 25)
    halftone(img, int(W * 0.56), int(H * 0.06), 16, 7, 34 * S // 2 * 2, 7 * S // 2 * 2, alpha=70)
    swooshes(img, int(W * 0.78), int(H * 0.52), int(W * 0.3), int(H * 0.38), rot=-14, width=9 * S)
    # phone
    if not blank:
        ph = phone_layer(int(265 * S), int(530 * S), icon, back=back).rotate(-9, resample=Image.BICUBIC, expand=True)
        img.alpha_composite(ph, (int(W * 0.655), int(H * 0.07)))
    d = ImageDraw.Draw(img, 'RGBA')
    lx = 70 * S
    logo = fit(full_light, h=118 * S); img.alpha_composite(logo, (lx - 6, 48 * S))
    if not blank:
        y = 200 * S
        if eyebrow:
            f = inter(22 * S, 'Bold'); d.text((lx, y), eyebrow.upper(), font=f, fill=SKY); y += 44 * S
        tf = mont(56 * S if len(title) < 30 else 50 * S, 800)
        maxw = int(W * 0.56)
        for line in wrap(d, title, tf, maxw):
            d.text((lx, y), line, font=tf, fill=WHITE); y += int(tf.size * 1.12)
        y += 10 * S
        sf = inter(27 * S, 'Medium')
        for line in wrap(d, sub, sf, maxw):
            d.text((lx, y), line, font=sf, fill=(205, 225, 250)); y += int(sf.size * 1.35)
        y += 16 * S
        cf = inter(19 * S, 'SemiBold'); x = lx
        if y + 44 * S < H - 76 * S:
            for c in chips:
                x += pill(d, x, y, c, cf, img=img) + 12 * S
            d = ImageDraw.Draw(img, 'RGBA')
    # bottom bar
    d.rectangle((0, H - 64 * S, W, H), fill=(0, 12, 38, 200))
    d.rectangle((0, H - 64 * S, W, H - 64 * S + 4 * S), fill=SKY)
    bf = inter(22 * S, 'SemiBold')
    d.text((lx, H - 32 * S), 'zrtechnologiesltd.com', font=bf, fill=WHITE, anchor='lm')
    d.text((W - lx, H - 32 * S), '07360 127538  •  Blackburn, Lancashire', font=bf, fill=(205, 225, 250), anchor='rm')
    out = img.convert('RGB').resize((1200, 630), Image.LANCZOS)
    out.save(f"{D['og']}/og-{slug}.jpg", quality=88, optimize=True, progressive=True)
    return out

PAGES = [
    ('home', 'Blackburn & surrounding areas', 'Expert iPhone Repair at Your Door', 'We collect your iPhone, repair it and bring it back — book online in minutes.', 'logo'),
    ('iphone-repair', 'iPhone repair Blackburn', 'iPhone Repair at Your Door', 'Screens, batteries, charging ports, cameras & more — quality parts with warranty.', 'shield'),
    ('screen-replacement', 'iPhone screen replacement', 'Cracked iPhone Screen? Fixed Fast.', 'Smashed, flickering or unresponsive display replaced with quality parts — collected & returned.', 'crack'),
    ('battery-replacement', 'iPhone battery replacement', 'New Battery, All-Day iPhone', 'Draining fast or shutting down? We replace worn batteries and restore your battery life.', 'battery'),
    ('charging-port-repair', 'iPhone charging port repair', 'iPhone Not Charging?', 'Loose, dirty or damaged charging ports cleaned, repaired or replaced.', 'charging'),
    ('back-glass-repair', 'iPhone back glass repair', 'Cracked Back Glass Repaired', 'Shattered rear glass replaced so your iPhone looks and feels like new again.', 'back', True),
    ('camera-repair', 'iPhone camera repair', 'Blurry or Broken Camera?', 'Rear camera, front camera and cracked camera lens repairs for every iPhone model.', 'camera'),
    ('water-damage-repair', 'iPhone water damage', 'Dropped Your iPhone in Water?', 'Act fast. Professional cleaning, diagnosis and repair for liquid-damaged iPhones.', 'water'),
    ('diagnostics', 'iPhone diagnostics', 'Not Sure What’s Wrong?', 'Full diagnostic check by an expert technician — we find the fault before we fix it.', 'search'),
    ('book-repair', 'Book online', 'Book Your iPhone Repair Online', 'Choose your model, pick a collection slot and we’ll handle the rest.', 'calendar'),
    ('repair-prices', 'Instant prices', 'iPhone Repair Prices', 'Pick your model and repair to see your price instantly — no hidden fees.', 'price'),
    ('how-it-works', 'Collection & delivery', 'How Our Door-to-Door Repair Works', 'Book online → we collect → expert repair → returned to your door.', 'van'),
    ('areas-we-cover', 'Local service', 'Areas We Cover', 'Collection & delivery iPhone repair across Blackburn and the surrounding areas.', 'pin'),
    ('track-repair', 'Repair tracker', 'Track Your Repair', 'Enter your job reference to see live progress on your iPhone repair.', 'search'),
    ('about', 'About us', 'About ZR Technologies', 'A Blackburn tech company built on honest pricing, quality parts and real care.', 'logo'),
    ('contact', 'Get in touch', 'Contact ZR Technologies', 'Call, WhatsApp or message us — we’re here to help with your repair.', 'chat'),
    ('faqs', 'Help centre', 'iPhone Repair FAQs', 'Answers on pricing, timings, warranty, data and how collection works.', 'question'),
    ('warranty', 'Peace of mind', 'Repair Warranty', 'Every repair is backed by our warranty — here’s what’s covered.', 'shield'),
    ('blog', 'Guides & tips', 'Phone Care Guides & Tips', 'Expert advice on protecting your iPhone, battery health, screens and more.', 'doc'),
    ('videos', 'Watch', 'Repair Videos & Shorts', 'See how we repair iPhones — quick videos from the ZR Technologies workshop.', 'play'),
    ('business', 'For businesses', 'Business & Bulk iPhone Repairs', 'Fleet, office and school device repairs with collection on your schedule.', 'briefcase'),
]
for p in PAGES:
    slug, eb, t, s, ic = p[:5]; back = len(p) > 5 and p[5]
    og_image(slug, eb, t, s, ic, back=back)

og_image('template-blank', None, '', '', 'logo', blank=True)
# blank template without the bottom-left content but with phone - for blog dynamic overlays: keep as default

# ---------------- SOCIAL / GBP ----------------
def banner(name, W, H, logo_h, safe=None, phone=True, tagline=True, scale=1.0):
    img = gradient(W, H, BRAND_STOPS, 20)
    halftone(img, int(W * 0.58), int(H * 0.08), 18, 6, max(8, int(H * 0.05)), max(2, int(H * 0.012)), alpha=60)
    swooshes(img, int(W * 0.72), int(H * 0.55), int(W * 0.32), int(H * 0.42), rot=-10, width=max(3, int(H * 0.015)))
    sx0, sy0, sx1, sy1 = safe or (0, 0, W, H)
    sw, sh = sx1 - sx0, sy1 - sy0
    d = ImageDraw.Draw(img, 'RGBA')
    logo = fit(full_light, h=int(logo_h))
    lx = sx0 + int(sw * 0.04); ly = sy0 + (sh - logo.height) // 2 - (int(sh * 0.12) if tagline else 0)
    img.alpha_composite(logo, (lx, ly))
    if tagline:
        f = mont(max(14, int(sh * 0.085)), 700); tx = lx + logo.width + int(sw * 0.04)
        d.text((tx, sy0 + sh * 0.36), 'iPhone Repair — We Collect,', font=f, fill=WHITE, anchor='lm')
        d.text((tx, sy0 + sh * 0.36 + f.size * 1.2), 'Repair & Return', font=f, fill=WHITE, anchor='lm')
        f2 = inter(max(12, int(sh * 0.05)), 'SemiBold')
        d.text((tx, sy0 + sh * 0.36 + f.size * 2.5), 'Blackburn & surrounding areas  •  07360 127538', font=f2, fill=(205, 225, 250), anchor='lm')
        d.text((tx, sy0 + sh * 0.36 + f.size * 2.5 + f2.size * 1.6), 'zrtechnologiesltd.com', font=f2, fill=SKY, anchor='lm')
    if phone and tagline:
        room = sx1 - (tx + max(d.textlength('iPhone Repair — We Collect,', font=f), d.textlength('Blackburn & surrounding areas  •  07360 127538', font=f2))) - int(sw * 0.03)
        phone = room > sh * 0.45
    if phone:
        ph_h = int(sh * 0.95); ph = phone_layer(int(ph_h * 0.5), ph_h, 'logo').rotate(-9, resample=Image.BICUBIC, expand=True)
        ph = fit(ph, h=int(sh * 1.05))
        img.alpha_composite(ph, (sx1 - ph.width - int(sw * 0.01), sy0 + (sh - ph.height) // 2))
    img.convert('RGB').save(f"{D['social']}/{name}", quality=90, optimize=True)

banner('facebook-cover-1640x624.jpg', 1640, 624, 220, safe=(170, 60, 1470, 564))
banner('linkedin-company-banner-1128x191.jpg', 1128, 191, 120, safe=(200, 10, 1128, 181), phone=False)
banner('x-twitter-header-1500x500.jpg', 1500, 500, 200, safe=(300, 50, 1450, 450))
banner('youtube-channel-banner-2560x1440.jpg', 2560, 1440, 300, safe=(507, 509, 2053, 932))
banner('google-business-cover-1024x576.jpg', 1024, 576, 200, safe=(30, 60, 994, 516))
banner('email-header-1200x300.jpg', 1200, 300, 170, safe=(30, 20, 1170, 280))

# profile pictures (square, circle-safe)
def profile(name, size, bg='white'):
    S = size * 2
    if bg == 'white': img = Image.new('RGBA', (S, S), WHITE + (255,)); src = full
    else: img = gradient(S, S, BRAND_STOPS, 135); src = full_light
    m = fit(src, int(S * 0.66), int(S * 0.66)); img.alpha_composite(m, ((S - m.width) // 2, (S - m.height) // 2))
    img.convert('RGB').resize((size, size), Image.LANCZOS).save(f"{D['social']}/{name}", quality=92)
profile('profile-picture-800x800-white.jpg', 800)
profile('profile-picture-800x800-gradient.jpg', 800, 'grad')
profile('google-business-logo-720x720.jpg', 720)

# ---------------- HERO BACKGROUNDS (text is HTML, not baked in) ----------------
def hero(name, W, H, phone_box, mobile=False):
    img = gradient(W, H, BRAND_STOPS, 30)
    halftone(img, int(W * (0.1 if mobile else 0.55)), int(H * 0.05), 22, 8, int(min(W, H) * 0.04), int(min(W, H) * 0.01), alpha=55)
    swooshes(img, int(W * (0.5 if mobile else 0.72)), int(H * (0.7 if mobile else 0.52)), int(W * (0.55 if mobile else 0.33)), int(H * (0.25 if mobile else 0.42)), rot=-12, width=int(min(W, H) * 0.012))
    if phone_box:
        x, y, h = phone_box
        ph = phone_layer(int(h * 0.5), h, 'crack').rotate(-10, resample=Image.BICUBIC, expand=True)
        img.alpha_composite(ph, (x, y))
        ph2 = phone_layer(int(h * 0.42), int(h * 0.84), 'logo').rotate(7, resample=Image.BICUBIC, expand=True)
        img.alpha_composite(ph2, (x - int(h * 0.36), y + int(h * 0.16)))
    img = img.convert('RGB')
    img.save(f"{D['hero']}/{name}.webp", quality=82, method=6)
    img.save(f"{D['hero']}/{name}.jpg", quality=82, optimize=True, progressive=True)

hero('hero-home-desktop-2400x1200', 2400, 1200, (1560, 70, 960))
hero('hero-home-mobile-1080x1350', 1080, 1350, (420, 700, 560), mobile=True)
hero('page-header-bg-1920x480', 1920, 480, None)
hero('cta-band-bg-1920x400', 1920, 400, None)
print('done')

# =====================================================================
# v2: "WHAT WE REPAIR" ICON SET + SERVICE-GRID ASSETS
# =====================================================================
REPAIRS = [
    ('screen', 'Screen & LCD'), ('battery', 'Battery'), ('charging', 'Charging Port'),
    ('camera', 'Camera & Lens'), ('backglass', 'Back Glass'), ('water', 'Water Damage'),
    ('speaker', 'Speaker & Mic'), ('faceid', 'Face ID'), ('buttons', 'Power & Volume'),
    ('software', 'Software Issues'), ('data', 'Data Recovery'), ('diagnostics', 'Free Diagnosis'),
]
YEL = (250, 204, 21); GRN = (74, 222, 128); RED = (248, 113, 113)

def ricon(img, key, cx, cy, s, col=WHITE, acc=SKY):
    """line-style repair icon centred at cx,cy, size s (draw at large size, it's supersampled)"""
    L = Image.new('RGBA', img.size, (0, 0, 0, 0)); d = ImageDraw.Draw(L)
    lw = max(2, int(s * 0.075)); C = col + (255,)
    def phone(w=0.5, h=0.9, ox=0, oy=0):
        x0, y0 = cx - s * w / 2 + ox, cy - s * h / 2 + oy
        d.rounded_rectangle((x0, y0, x0 + s * w, y0 + s * h), radius=int(s * 0.1), outline=C, width=lw)
        return x0, y0, x0 + s * w, y0 + s * h
    if key == 'screen':
        x0, y0, x1, y1 = phone()
        d.rounded_rectangle((cx - s * 0.06, y0 + s * 0.07, cx + s * 0.06, y0 + s * 0.11), radius=3, fill=C)
        px, py = cx + s * 0.04, cy + s * 0.02
        for ex, ey in [(x0 + lw, y0 + s * 0.3), (x1 - lw, y0 + s * 0.22), (x1 - lw, y1 - s * 0.25), (cx - s * 0.12, y1 - lw), (x0 + lw, cy + s * 0.2)]:
            mx, my = (px + ex) / 2 + s * 0.03, (py + ey) / 2 - s * 0.02
            d.line([(px, py), (mx, my), (ex, ey)], fill=acc + (255,), width=max(2, lw // 2), joint='curve')
    elif key == 'battery':
        x0, y0 = cx - s * 0.42, cy - s * 0.22
        d.rounded_rectangle((x0, y0, x0 + s * 0.76, y0 + s * 0.44), radius=int(s * 0.08), outline=C, width=lw)
        d.rounded_rectangle((x0 + s * 0.78, cy - s * 0.09, x0 + s * 0.86, cy + s * 0.09), radius=3, fill=C)
        for i in range(3):
            bx = x0 + s * 0.08 + i * s * 0.22
            d.rounded_rectangle((bx, y0 + s * 0.09, bx + s * 0.16, y0 + s * 0.35), radius=3, fill=GRN + (255,))
    elif key == 'charging':
        d.rounded_rectangle((cx - s * 0.17, cy + s * 0.02, cx + s * 0.17, cy + s * 0.3), radius=int(s * 0.07), outline=C, width=lw)
        d.rectangle((cx - s * 0.1, cy - s * 0.06, cx + s * 0.1, cy + s * 0.02), fill=C)
        d.line([(cx, cy + s * 0.3), (cx, cy + s * 0.48)], fill=C, width=lw)
        b = s * 0.36; by = cy - s * 0.26
        pts = [(cx + b * 0.12, by - b * 0.5), (cx - b * 0.3, by + b * 0.06), (cx - b * 0.02, by + b * 0.06), (cx - b * 0.12, by + b * 0.5), (cx + b * 0.3, by - b * 0.08), (cx + b * 0.02, by - b * 0.08)]
        d.polygon(pts, fill=YEL + (255,))
    elif key == 'camera':
        w = s * 0.78; x0, y0 = cx - w / 2, cy - w / 2
        d.rounded_rectangle((x0, y0, x0 + w, y0 + w), radius=int(w * 0.24), outline=C, width=lw)
        for fx, fy in [(0.31, 0.31), (0.31, 0.71), (0.71, 0.51)]:
            r = w * 0.15; X, Y = x0 + w * fx, y0 + w * fy
            d.ellipse((X - r, Y - r, X + r, Y + r), outline=C, width=lw)
            d.ellipse((X - r * 0.35, Y - r * 0.35, X + r * 0.35, Y + r * 0.35), fill=acc + (255,))
        r = w * 0.05; d.ellipse((x0 + w * 0.71 - r, y0 + w * 0.24 - r, x0 + w * 0.71 + r, y0 + w * 0.24 + r), fill=YEL + (255,))
    elif key == 'backglass':
        x0, y0, x1, y1 = phone()
        q = s * 0.2; d.rounded_rectangle((x0 + s * 0.07, y0 + s * 0.07, x0 + s * 0.07 + q, y0 + s * 0.07 + q), radius=int(q * 0.3), outline=C, width=max(2, lw // 1))
        px, py = cx + s * 0.05, y1 - s * 0.25
        for ex, ey in [(x0 + lw, y1 - s * 0.4), (x1 - lw, y1 - s * 0.45), (x1 - lw, y1 - s * 0.08), (x0 + s * 0.08, y1 - lw)]:
            d.line([(px, py), (ex, ey)], fill=acc + (255,), width=max(2, lw // 2))
    elif key == 'water':
        r = s * 0.27; Y = cy + s * 0.1
        d.ellipse((cx - r, Y - r, cx + r, Y + r), fill=(125, 200, 255, 255))
        d.polygon([(cx - r * 0.93, Y - r * 0.35), (cx + r * 0.93, Y - r * 0.35), (cx, cy - s * 0.42)], fill=(125, 200, 255, 255))
        d.arc((cx - r * 0.6, Y - r * 0.6, cx + r * 0.6, Y + r * 0.6), 100, 170, fill=WHITE + (255,), width=max(2, lw // 1))
        rr = s * 0.08; d.ellipse((cx + s * 0.3 - rr, cy - s * 0.2 - rr, cx + s * 0.3 + rr, cy - s * 0.2 + rr), fill=(125, 200, 255, 200))
    elif key == 'speaker':
        x0 = cx - s * 0.38
        d.polygon([(x0, cy - s * 0.12), (x0 + s * 0.15, cy - s * 0.12), (x0 + s * 0.36, cy - s * 0.32), (x0 + s * 0.36, cy + s * 0.32), (x0 + s * 0.15, cy + s * 0.12), (x0, cy + s * 0.12)], fill=C)
        for k, al in [(0.22, 255), (0.36, 200)]:
            ax = x0 + s * 0.36
            d.arc((ax - s * k, cy - s * k, ax + s * k, cy + s * k), -50, 50, fill=acc + (al,), width=lw)
    elif key == 'faceid':
        w = s * 0.78; x0, y0 = cx - w / 2, cy - w / 2; k = w * 0.28
        for (ax, ay, sx, sy) in [(x0, y0, 1, 1), (x0 + w, y0, -1, 1), (x0, y0 + w, 1, -1), (x0 + w, y0 + w, -1, -1)]:
            d.line([(ax, ay + sy * k), (ax, ay), (ax + sx * k, ay)], fill=C, width=lw, joint='curve')
        e = w * 0.06
        for ex in (cx - w * 0.17, cx + w * 0.17): d.rounded_rectangle((ex - e / 2, cy - w * 0.16, ex + e / 2, cy - w * 0.04), radius=3, fill=acc + (255,))
        d.line([(cx, cy - w * 0.12), (cx, cy + w * 0.04), (cx - w * 0.05, cy + w * 0.04)], fill=acc + (255,), width=max(2, lw // 1))
        d.arc((cx - w * 0.2, cy - w * 0.05, cx + w * 0.2, cy + w * 0.22), 20, 160, fill=acc + (255,), width=lw)
    elif key == 'buttons':
        x0, y0, x1, y1 = phone(0.42, 0.86)
        bw = max(3, int(lw * 1.1))
        d.rounded_rectangle((x0 - bw - lw * 0.4, y0 + s * 0.17, x0 - lw * 0.4, y0 + s * 0.29), radius=2, fill=acc + (255,))
        d.rounded_rectangle((x0 - bw - lw * 0.4, y0 + s * 0.33, x0 - lw * 0.4, y0 + s * 0.45), radius=2, fill=acc + (255,))
        d.rounded_rectangle((x1 + lw * 0.4, y0 + s * 0.22, x1 + bw + lw * 0.4, y0 + s * 0.4), radius=2, fill=YEL + (255,))
    elif key == 'software':
        r = s * 0.32
        d.arc((cx - r, cy - r, cx + r, cy + r), 30, 300, fill=C, width=lw)
        a = math.radians(300); ex, ey = cx + r * math.cos(a), cy + r * math.sin(a)
        h = s * 0.15; d.polygon([(ex + h * 0.9, ey + h * 0.05), (ex - h * 0.2, ey - h * 0.75), (ex - h * 0.35, ey + h * 0.6)], fill=C)
        gr = s * 0.12; d.ellipse((cx - gr, cy - gr, cx + gr, cy + gr), outline=acc + (255,), width=lw)
        for i in range(8):
            a = math.radians(i * 45); d.line([(cx + gr * 1.05 * math.cos(a), cy + gr * 1.05 * math.sin(a)), (cx + gr * 1.55 * math.cos(a), cy + gr * 1.55 * math.sin(a))], fill=acc + (255,), width=max(2, lw // 1))
    elif key == 'data':
        Y = cy - s * 0.05
        for (ox, oy, r) in [(-0.2, 0.05, 0.17), (0.0, -0.08, 0.23), (0.22, 0.04, 0.17)]:
            d.ellipse((cx + s * ox - s * r, Y + s * oy - s * r, cx + s * ox + s * r, Y + s * oy + s * r), fill=C)
        d.rounded_rectangle((cx - s * 0.37, Y, cx + s * 0.39, Y + s * 0.2), radius=int(s * 0.1), fill=C)
        ax = cx; d.line([(ax, Y + s * 0.42), (ax, Y + s * 0.02)], fill=NAVY + (255,), width=lw + 2)
        d.line([(ax, Y + s * 0.42), (ax, Y + s * 0.12)], fill=acc + (255,), width=lw)
        d.polygon([(ax - s * 0.1, Y + s * 0.14), (ax + s * 0.1, Y + s * 0.14), (ax, Y + s * 0.01)], fill=acc + (255,))
    elif key == 'diagnostics':
        r = s * 0.24; X, Y = cx - s * 0.07, cy - s * 0.07
        d.ellipse((X - r, Y - r, X + r, Y + r), outline=C, width=lw)
        d.line([(X + r * 0.72, Y + r * 0.72), (cx + s * 0.38, cy + s * 0.38)], fill=C, width=int(lw * 1.6))
        d.line([(X - r * 0.65, Y), (X - r * 0.25, Y), (X - r * 0.08, Y - r * 0.45), (X + r * 0.12, Y + r * 0.4), (X + r * 0.3, Y), (X + r * 0.65, Y)], fill=GRN + (255,), width=max(2, lw // 1), joint='curve')
    img.alpha_composite(L)

def tile_grid(img, x, y, w, h, cols, rows, S, label_size, icon_ratio=0.46, items=REPAIRS, light=True, gap=None):
    gap = gap or int(min(w / cols, h / rows) * 0.09)
    tw = (w - gap * (cols - 1)) / cols; th = (h - gap * (rows - 1)) / rows
    lay = Image.new('RGBA', img.size, (0, 0, 0, 0)); dl = ImageDraw.Draw(lay)
    for i, (key, lab) in enumerate(items[:cols * rows]):
        cx0 = x + (i % cols) * (tw + gap); cy0 = y + (i // cols) * (th + gap)
        dl.rounded_rectangle((cx0, cy0, cx0 + tw, cy0 + th), radius=int(min(tw, th) * 0.16),
                             fill=(255, 255, 255, 24) if light else (255, 255, 255, 255), outline=(255, 255, 255, 70) if light else (200, 215, 240, 255), width=max(2, S))
    img.alpha_composite(lay)
    d = ImageDraw.Draw(img); ls = int(label_size)
    longest = max((lab for _, lab in items[:cols * rows]), key=lambda t: d.textlength(t, font=inter(ls, 'SemiBold')))
    while ls > 8 and d.textlength(longest, font=inter(ls, 'SemiBold')) > tw * 0.86: ls -= 1
    f = inter(ls, 'SemiBold')
    for i, (key, lab) in enumerate(items[:cols * rows]):
        cx0 = x + (i % cols) * (tw + gap); cy0 = y + (i // cols) * (th + gap)
        isz = min(tw, th) * icon_ratio
        icy = cy0 + th * 0.40
        ricon(img, key, cx0 + tw / 2, icy, isz, col=WHITE if light else NAVY, acc=SKY if light else ROYAL)
        d.text((cx0 + tw / 2, cy0 + th * 0.83), lab, font=f, fill=WHITE if light else NAVY, anchor='mm')

def bottom_bar(img, S, lx):
    W, H = img.size; d = ImageDraw.Draw(img, 'RGBA')
    d.rectangle((0, H - 64 * S, W, H), fill=(0, 12, 38, 210)); d.rectangle((0, H - 64 * S, W, H - 60 * S), fill=SKY)
    bf = inter(22 * S, 'SemiBold')
    d.text((lx, H - 32 * S), 'zrtechnologiesltd.com', font=bf, fill=WHITE, anchor='lm')
    d.text((W - lx, H - 32 * S), '07360 127538  •  Collection & delivery across Blackburn', font=bf, fill=(205, 225, 250), anchor='rm')

def og_services(slug, title, sub):
    S = 2; W, H = 1200 * S, 630 * S
    img = gradient(W, H, BRAND_STOPS, 25)
    halftone(img, int(W * 0.80), int(H * 0.80), 12, 2, 26 * S, 5 * S, alpha=45)
    swooshes(img, int(W * 0.5), int(H * 0.5), int(W * 0.58), int(H * 0.5), rot=-6, width=7 * S, alpha=110)
    lx = 56 * S
    logo = fit(full_light, h=104 * S); img.alpha_composite(logo, (lx - 4, 30 * S))
    d = ImageDraw.Draw(img)
    tx = lx + logo.width + 34 * S
    tf = mont(44 * S, 800); d.text((tx, 52 * S), title, font=tf, fill=WHITE)
    sf = inter(23 * S, 'Medium'); d.text((tx, 108 * S), sub, font=sf, fill=(205, 225, 250))
    tile_grid(img, lx, 162 * S, W - 2 * lx, 330 * S, 6, 2, S, 18 * S)
    bottom_bar(img, S, lx)
    img.convert('RGB').resize((1200, 630), Image.LANCZOS).save(f"{D['og']}/og-{slug}.jpg", quality=90, optimize=True, progressive=True)

og_services('default', 'iPhone Repair — We Collect & Return', 'Expert repairs with quality parts & warranty. Book online in minutes.')
og_services('home', 'iPhone Repair — We Collect & Return', 'Expert repairs with quality parts & warranty. Book online in minutes.')
og_services('iphone-repair', 'Every iPhone Repair, One Visit', 'Screens, batteries, charging ports, cameras & more — at your door.')
og_services('repair-prices', 'iPhone Repair Prices', 'Pick your model & repair for an instant price — no hidden fees.')

# ----- icon exports for the website "What we repair" section -----
os.makedirs(f"{OUT}/icons", exist_ok=True)
for key, lab in REPAIRS:
    for name, col, acc in [('navy', NAVY, ROYAL), ('white', WHITE, SKY)]:
        c = Image.new('RGBA', (512, 512), (0, 0, 0, 0)); ricon(c, key, 256, 256, 400, col=col, acc=acc)
        c.resize((256, 256), Image.LANCZOS).save(f"{OUT}/icons/icon-{key}-{name}.png")

# ----- social posts -----
def social_post(name, W, H, cols, rows, title, grid_box, title_y, label, cta_y, logo_h=0.2):
    img = gradient(W, H, BRAND_STOPS, 30)
    halftone(img, int(W * 0.06), int(H * 0.93), 14, 2, int(W * 0.03), int(W * 0.005), alpha=40)
    swooshes(img, W // 2, int(H * 0.55), int(W * 0.62), int(H * 0.42), rot=-8, width=int(W * 0.007), alpha=110)
    logo = fit(full_light, h=int(W * logo_h)); img.alpha_composite(logo, ((W - logo.width) // 2, int(W * 0.04)))
    d = ImageDraw.Draw(img)
    tf = mont(int(W * 0.062), 800)
    for i, line in enumerate(title):
        d.text((W // 2, title_y + i * tf.size * 1.15), line, font=tf, fill=WHITE, anchor='mm')
    tile_grid(img, *grid_box, cols, rows, 2, label)
    f = inter(int(W * 0.03), 'SemiBold'); f2 = mont(int(W * 0.036), 800)
    # CTA pill
    t = 'Book online — we collect, repair & return'
    tw = d.textlength(t, font=f2); ph = f2.size * 2
    d.rounded_rectangle((W / 2 - tw / 2 - 40, cta_y, W / 2 + tw / 2 + 40, cta_y + ph), radius=ph // 2, fill=WHITE)
    d.text((W / 2, cta_y + ph / 2), t, font=f2, fill=NAVY, anchor='mm')
    d.text((W / 2, cta_y + ph + f.size * 1.4), 'zrtechnologiesltd.com  •  07360 127538', font=f, fill=(205, 225, 250), anchor='mm')
    img.convert('RGB').save(f"{D['social']}/{name}", quality=90, optimize=True)

social_post('instagram-post-what-we-repair-1080x1080.jpg', 1080, 1080, 4, 3, ['What We Repair'], (60, 365, 960, 495), 312, 26, 890, logo_h=0.2)
social_post('instagram-story-what-we-repair-1080x1920.jpg', 1080, 1920, 3, 4, ['iPhone Repair', 'We Collect & Return'], (70, 640, 940, 900), 450, 36, 1620, logo_h=0.3)
social_post('facebook-post-what-we-repair-1200x1500.jpg', 1200, 1500, 3, 4, ['What We Repair'], (70, 400, 1060, 860), 345, 32, 1310, logo_h=0.22)

# ----- service-grid banners (replace earlier versions) -----
def banner2(name, W, H, safe, cols, rows, title_size, logo_frac=0.62, label_frac=0.11):
    img = gradient(W, H, BRAND_STOPS, 20)
    halftone(img, int(W * 0.86), int(H * 0.06), 8, 4, max(8, int(H * 0.04)), max(2, int(H * 0.009)), alpha=40)
    swooshes(img, int(W * 0.62), int(H * 0.55), int(W * 0.4), int(H * 0.45), rot=-8, width=max(3, int(H * 0.012)), alpha=110)
    sx0, sy0, sx1, sy1 = safe; sw, sh = sx1 - sx0, sy1 - sy0
    logo = fit(full_light, h=int(sh * logo_frac), w=int(sw * 0.2))
    img.alpha_composite(logo, (sx0, sy0 + (sh - logo.height) // 2))
    gx = sx0 + logo.width + int(sw * 0.04); gw = sx1 - gx
    d = ImageDraw.Draw(img)
    tf = mont(int(title_size), 800); d.text((gx, sy0), 'iPhone Repair — We Collect, Repair & Return', font=tf, fill=WHITE)
    cf = inter(int(title_size * 0.55), 'SemiBold')
    gy = sy0 + int(tf.size * 1.5); gh = sh - int(tf.size * 1.5) - int(cf.size * 1.9)
    tile_grid(img, gx, gy, gw, gh, cols, rows, 2, int(gh / rows * 0.15))
    d = ImageDraw.Draw(img)
    d.text((gx, sy1), 'Blackburn & surrounding areas  •  07360 127538  •  zrtechnologiesltd.com', font=cf, fill=(205, 225, 250), anchor='ld')
    img.convert('RGB').save(f"{D['social']}/{name}", quality=90, optimize=True)

banner2('facebook-cover-1640x624.jpg', 1640, 624, (175, 60, 1465, 564), 4, 3, 40, logo_frac=0.55)
banner2('x-twitter-header-1500x500.jpg', 1500, 500, (250, 40, 1450, 460), 4, 3, 32, logo_frac=0.55)
banner2('google-business-cover-1024x576.jpg', 1024, 576, (40, 60, 984, 516), 4, 3, 28, logo_frac=0.5)
banner2('youtube-channel-banner-2560x1440.jpg', 2560, 1440, (520, 520, 2040, 920), 6, 2, 34)
banner2('email-header-1200x300.jpg', 1200, 300, (30, 24, 1170, 276), 6, 2, 22)

# LinkedIn: too thin for tiles -> text list
img = gradient(1128, 191, BRAND_STOPS, 20); swooshes(img, 700, 110, 420, 90, rot=-5, width=3, alpha=110)
logo = fit(full_light, h=120); img.alpha_composite(logo, (230, 36)); d = ImageDraw.Draw(img)
d.text((410, 52), 'iPhone Repair — We Collect, Repair & Return', font=mont(24, 800), fill=WHITE)
d.text((410, 92), 'Screens & LCD • Batteries • Charging Ports • Cameras • Back Glass', font=inter(15, 'SemiBold'), fill=(205, 225, 250))
d.text((410, 116), 'Water Damage • Speakers & Mics • Face ID • Buttons • Software • Data Recovery', font=inter(15, 'SemiBold'), fill=(205, 225, 250))
d.text((410, 146), 'zrtechnologiesltd.com  •  07360 127538', font=inter(15, 'Bold'), fill=SKY)
img.convert('RGB').save(f"{D['social']}/linkedin-company-banner-1128x191.jpg", quality=92)
print('v2 done')
