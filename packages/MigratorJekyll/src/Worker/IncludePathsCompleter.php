<?php declare(strict_types=1);

namespace Symplify\Statie\MigratorJekyll\Worker;

use Nette\Utils\FileSystem;
use Nette\Utils\Strings;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symplify\PackageBuilder\FileSystem\SmartFileInfo;
use Symplify\Statie\MigratorJekyll\Contract\MigratorJekyllWorkerInterface;
use Symplify\Statie\MigratorJekyll\Filesystem\MigratorFilesystem;
use function Safe\sprintf;

final class IncludePathsCompleter implements MigratorJekyllWorkerInterface
{
    /**
     * @var SymfonyStyle
     */
    private $symfonyStyle;

    /**
     * @var MigratorFilesystem
     */
    private $migratorFilesystem;

    public function __construct(SymfonyStyle $symfonyStyle, MigratorFilesystem $migratorFilesystem)
    {
        $this->symfonyStyle = $symfonyStyle;
        $this->migratorFilesystem = $migratorFilesystem;
    }

    public function processSourceDirectory(string $sourceDirectory, string $workingDirectory): void
    {
        $fileInfos = $this->migratorFilesystem->findFiles($sourceDirectory, $workingDirectory);

        $includeableFileInfos = $this->migratorFilesystem->findIncludeableFiles($sourceDirectory, $workingDirectory);

        foreach ($fileInfos as $fileInfo) {
            $oldContent = $fileInfo->getContents();
            $newContent = $this->completePaths($sourceDirectory, $oldContent, $includeableFileInfos);
            if ($oldContent === $newContent) {
                continue;
            }

            FileSystem::write($fileInfo->getRealPath(), $newContent);

            $this->symfonyStyle->note(sprintf(
                'Include paths changed in "%s" file',
                $fileInfo->getRelativeFilePathFromDirectory($workingDirectory)
            ));
        }

        $this->symfonyStyle->success('Include paths were completed');
    }

    /**
     * @param SmartFileInfo[] $includeableFileInfos
     */
    private function completePaths(string $sourceDirectory, string $oldContent, array $includeableFileInfos): string
    {
        $newContent = Strings::replace(
            $oldContent,
            '#(?<open>{% include )(?<shortPath>.*?)(?<close> %})#',
            function (array $match) use ($includeableFileInfos, $sourceDirectory) {
                $fullPath = $this->matchShortPathToFull($match['shortPath'], $includeableFileInfos, $sourceDirectory);
                $fullPath = Strings::replace($fullPath, '#\.html$#', '.twig');

                return sprintf('%s\'%s\'%s', $match['open'], $fullPath, $match['close']);
            }
        );

        // https://regex101.com/r/C0tbeX/1
        return Strings::replace(
            $newContent,
            '#(?<open>---.*?\s*layout:\s*)(?<shortPath>.*?)(?<close>\n)#',
            function (array $match) use ($includeableFileInfos, $sourceDirectory) {
                $fullPath = $this->matchShortPathToFull($match['shortPath'], $includeableFileInfos, $sourceDirectory);
                $fullPath = Strings::replace($fullPath, '#\.html$#', '.twig');

                return $match['open'] . $fullPath . $match['close'];
            }
        );
    }

    /**
     * @param SmartFileInfo[] $includeableFileInfos
     */
    private function matchShortPathToFull(
        string $shortPath,
        array $includeableFileInfos,
        string $sourceDirectory
    ): string {
        foreach ($includeableFileInfos as $includeableFileInfo) {
            if ($includeableFileInfo->getBasename() === $shortPath) {
                return $includeableFileInfo->getRelativeFilePathFromDirectory($sourceDirectory);
            }

            if ($includeableFileInfo->getBasename('.html') === $shortPath) {
                return $includeableFileInfo->getRelativeFilePathFromDirectory($sourceDirectory);
            }
        }

        return $shortPath;
    }
}
