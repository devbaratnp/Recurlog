import sharp from 'sharp';
import fs from 'fs';
import path from 'path';

const INPUT = 'assets/icons/icon-192.png';
const OUTPUT_DIR = 'assets/icons';

const SIZES = [48, 72, 96, 128, 144, 152, 384, 512];

fs.mkdirSync(OUTPUT_DIR, { recursive: true });

async function generateMaskable(size) {
  const maskablePath = path.join(OUTPUT_DIR, `icon-${size}-maskable.png`);
  const paddedSize = Math.round(size * 0.8);
  const offset = Math.round(size * 0.1);
  await sharp({
    create: {
      width: size,
      height: size,
      channels: 4,
      background: { r: 249, g: 250, b: 251, alpha: 1 },
    },
  })
    .composite([
      {
        input: await sharp(INPUT).resize(paddedSize, paddedSize).png().toBuffer(),
        left: offset,
        top: offset,
      },
    ])
    .png()
    .toFile(maskablePath);
  console.log(`Generated ${maskablePath} (maskable)`);
}

async function main() {
  for (const size of SIZES) {
    const outPath = path.join(OUTPUT_DIR, `icon-${size}.png`);
    await sharp(INPUT)
      .resize(size, size)
      .png()
      .toFile(outPath);
    console.log(`Generated ${outPath}`);
  }

  // Generate maskable icons
  await generateMaskable(192);
  await generateMaskable(512);

  // Generate apple-icon 180x180
  await sharp(INPUT)
    .resize(180, 180)
    .png()
    .toFile(path.join(OUTPUT_DIR, 'apple-icon-180x180.png'));
  console.log('Generated apple-icon-180x180.png');
}

main().catch(console.error);
