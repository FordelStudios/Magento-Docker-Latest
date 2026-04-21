<?php
declare(strict_types=1);

namespace Formula\WebpUpload\Console\Command;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\State;
use Magento\Framework\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConvertImages extends Command
{
    /**
     * @param Filesystem $filesystem
     * @param State $appState
     * @param string|null $name
     */
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly State $appState,
        string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName('formula:images:convert')
            ->setDescription('Convert existing product images to AVIF and WebP formats');
        parent::configure();
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->appState->setAreaCode('adminhtml');
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            // Area code already set — safe to continue
        }

        $mediaDir = $this->filesystem
            ->getDirectoryRead(DirectoryList::MEDIA)
            ->getAbsolutePath('catalog/product');

        if (!is_dir($mediaDir)) {
            $output->writeln("<error>Directory not found: {$mediaDir}</error>");
            return Command::FAILURE;
        }

        $output->writeln("<info>Scanning: {$mediaDir}</info>");

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($mediaDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $converted = 0;
        $skipped   = 0;
        $failed    = 0;
        $processed = 0;

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $absolutePath = $file->getPathname();

            // Skip cache subdirectory
            if (strpos($absolutePath, DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR) !== false) {
                continue;
            }

            $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

            // Skip already-converted formats
            if (in_array($ext, ['avif', 'webp'], true)) {
                $skipped++;
                continue;
            }

            // Only process source formats
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'], true)) {
                $skipped++;
                continue;
            }

            $result = $this->convertFile($absolutePath, $ext);
            if ($result) {
                $converted++;
            } else {
                $failed++;
            }

            $processed++;
            if ($processed % 50 === 0) {
                $output->writeln(
                    "  Processed {$processed} files — converted: {$converted}, failed: {$failed}, skipped: {$skipped}"
                );
            }
        }

        $output->writeln('');
        $output->writeln('<info>Conversion complete.</info>');
        $output->writeln("  Converted : {$converted}");
        $output->writeln("  Failed    : {$failed}");
        $output->writeln("  Skipped   : {$skipped}");

        return Command::SUCCESS;
    }

    /**
     * Converts a single image file to WebP and AVIF.
     * Returns true if at least one format was written successfully.
     */
    private function convertFile(string $absolutePath, string $ext): bool
    {
        $image = $this->createImageResource($absolutePath, $ext);
        if ($image === false || $image === null) {
            return false;
        }

        $basePath = substr($absolutePath, 0, strrpos($absolutePath, '.'));
        $success  = false;

        // Generate WebP (quality 85)
        try {
            if (function_exists('imagewebp')) {
                if (imagewebp($image, $basePath . '.webp', 85)) {
                    $success = true;
                }
            }
        } catch (\Throwable $e) {
            // Silently skip
        }

        // Generate AVIF (quality 75) — guard for containers without libavif yet
        try {
            if (function_exists('imageavif')) {
                if (imageavif($image, $basePath . '.avif', 75)) {
                    $success = true;
                }
            }
        } catch (\Throwable $e) {
            // Silently skip
        }

        imagedestroy($image);

        return $success;
    }

    /**
     * Creates a GD image resource from a file path.
     * Handles PNG transparency correctly.
     *
     * @return \GdImage|false|null
     */
    private function createImageResource(string $absolutePath, string $ext)
    {
        try {
            switch ($ext) {
                case 'jpg':
                case 'jpeg':
                    return imagecreatefromjpeg($absolutePath);

                case 'png':
                    $src = imagecreatefrompng($absolutePath);
                    if ($src === false) {
                        return false;
                    }
                    $w     = imagesx($src);
                    $h     = imagesy($src);
                    $image = imagecreatetruecolor($w, $h);
                    if ($image === false) {
                        imagedestroy($src);
                        return false;
                    }
                    imagepalettetotruecolor($image);
                    imagealphablending($image, false);
                    imagesavealpha($image, true);
                    $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
                    imagefilledrectangle($image, 0, 0, $w, $h, $transparent);
                    imagealphablending($image, true);
                    imagecopy($image, $src, 0, 0, 0, 0, $w, $h);
                    imagedestroy($src);
                    return $image;

                case 'gif':
                    return imagecreatefromgif($absolutePath);

                default:
                    return false;
            }
        } catch (\Throwable $e) {
            return false;
        }
    }
}
