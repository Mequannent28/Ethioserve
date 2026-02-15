<?php
/**
 * Generate PWA icons from canvas
 * Open this page in your browser to download all required icon sizes
 */
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate EthioServe App Icons</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            padding: 40px;
            background: #f5f5f5;
        }

        h1 {
            color: #1B5E20;
        }

        .icon-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 20px;
        }

        .icon-item {
            background: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .icon-item canvas {
            display: block;
            margin: 0 auto 10px;
            border-radius: 20%;
        }

        .icon-item a {
            display: inline-block;
            margin-top: 8px;
            padding: 6px 16px;
            background: #1B5E20;
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.85rem;
        }

        .btn-all {
            padding: 14px 30px;
            background: #1B5E20;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <h1>🎨 EthioServe PWA Icon Generator</h1>
    <p>Click "Download All" to save icons, then place them in <code>/assets/icons/</code></p>
    <button class="btn-all" onclick="downloadAll()">⬇️ Download All Icons</button>

    <div class="icon-grid" id="iconGrid"></div>

    <script>
        const sizes = [72, 96, 128, 144, 152, 192, 384, 512];

        function drawIcon(canvas, size) {
            const ctx = canvas.getContext('2d');
            canvas.width = size;
            canvas.height = size;
            const s = size / 512; // scale factor

            // Background gradient
            const grad = ctx.createLinearGradient(0, 0, size, size);
            grad.addColorStop(0, '#1B5E20');
            grad.addColorStop(1, '#2E7D32');

            // Rounded rect background
            const r = 100 * s;
            ctx.beginPath();
            ctx.moveTo(r, 0);
            ctx.lineTo(size - r, 0);
            ctx.quadraticCurveTo(size, 0, size, r);
            ctx.lineTo(size, size - r);
            ctx.quadraticCurveTo(size, size, size - r, size);
            ctx.lineTo(r, size);
            ctx.quadraticCurveTo(0, size, 0, size - r);
            ctx.lineTo(0, r);
            ctx.quadraticCurveTo(0, 0, r, 0);
            ctx.closePath();
            ctx.fillStyle = grad;
            ctx.fill();

            // Inner circle
            ctx.beginPath();
            ctx.arc(256 * s, 230 * s, 130 * s, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(255,255,255,0.08)';
            ctx.fill();

            // Fork
            ctx.fillStyle = '#FFD600';
            const fx = 195 * s, fy = 150 * s;
            for (let i = 0; i < 3; i++) {
                roundRect(ctx, fx + i * 20 * s, fy, 7 * s, 70 * s, 3 * s);
            }
            roundRect(ctx, fx - 5 * s, fy + 60 * s, 57 * s, 10 * s, 5 * s);
            roundRect(ctx, fx + 14 * s, fy + 65 * s, 16 * s, 80 * s, 8 * s);

            // Location pin
            ctx.fillStyle = 'white';
            ctx.save();
            ctx.translate(310 * s, 150 * s);
            ctx.beginPath();
            ctx.moveTo(0, 65 * s);
            ctx.bezierCurveTo(-5 * s, 50 * s, -25 * s, 30 * s, -25 * s, 12 * s);
            ctx.arc(0, 12 * s, 25 * s, Math.PI, 0, false);
            ctx.bezierCurveTo(25 * s, 30 * s, 5 * s, 50 * s, 0, 65 * s);
            ctx.fill();
            // Pin inner circle
            ctx.beginPath();
            ctx.arc(0, 12 * s, 10 * s, 0, Math.PI * 2);
            ctx.fillStyle = '#2E7D32';
            ctx.fill();
            ctx.restore();

            // Text "EthioServe"
            ctx.textAlign = 'center';
            const fontSize = Math.max(12, 44 * s);
            ctx.font = `bold ${fontSize}px Arial, Helvetica, sans-serif`;
            ctx.fillStyle = 'white';
            const textY = 370 * s;
            const ethioWidth = ctx.measureText('Ethio').width;
            const serveWidth = ctx.measureText('Serve').width;
            const totalWidth = ethioWidth + serveWidth;
            const startX = (size - totalWidth) / 2;

            ctx.textAlign = 'left';
            ctx.fillStyle = 'white';
            ctx.fillText('Ethio', startX, textY);
            ctx.fillStyle = '#FFD600';
            ctx.fillText('Serve', startX + ethioWidth, textY);

            // Tagline
            const tagSize = Math.max(8, 20 * s);
            ctx.font = `${tagSize}px Arial, Helvetica, sans-serif`;
            ctx.textAlign = 'center';
            ctx.fillStyle = 'rgba(255,255,255,0.6)';
            ctx.fillText('All-in-One', size / 2, 400 * s);
        }

        function roundRect(ctx, x, y, w, h, r) {
            ctx.beginPath();
            ctx.moveTo(x + r, y);
            ctx.lineTo(x + w - r, y);
            ctx.quadraticCurveTo(x + w, y, x + w, y + r);
            ctx.lineTo(x + w, y + h - r);
            ctx.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
            ctx.lineTo(x + r, y + h);
            ctx.quadraticCurveTo(x, y + h, x, y + h - r);
            ctx.lineTo(x, y + r);
            ctx.quadraticCurveTo(x, y, x + r, y);
            ctx.closePath();
            ctx.fill();
        }

        // Generate icons
        const grid = document.getElementById('iconGrid');
        sizes.forEach(size => {
            const div = document.createElement('div');
            div.className = 'icon-item';

            const canvas = document.createElement('canvas');
            canvas.id = `icon-${size}`;
            drawIcon(canvas, size);

            const label = document.createElement('div');
            label.innerHTML = `<strong>${size}×${size}</strong>`;

            const link = document.createElement('a');
            link.href = canvas.toDataURL('image/png');
            link.download = `icon-${size}x${size}.png`;
            link.textContent = 'Download';

            div.appendChild(canvas);
            div.appendChild(label);
            div.appendChild(link);
            grid.appendChild(div);
        });

        function downloadAll() {
            sizes.forEach((size, i) => {
                setTimeout(() => {
                    const canvas = document.getElementById(`icon-${size}`);
                    const link = document.createElement('a');
                    link.href = canvas.toDataURL('image/png');
                    link.download = `icon-${size}x${size}.png`;
                    link.click();
                }, i * 300);
            });
        }
    </script>
</body>

</html>