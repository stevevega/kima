<?php
namespace Kima\Tests\Cache;

use Kima\Cache\File;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FileTest extends TestCase
{
    private string $folder;
    private string $source_file;
    private File $cache;
    private array $raised_errors = [];

    protected function setUp(): void
    {
        $this->folder = sys_get_temp_dir() . '/kima-cache-' . bin2hex(random_bytes(4));
        $this->cache = new File(['file' => ['folder' => $this->folder]]);
        $this->source_file = $this->folder . '/source.php';
        touch($this->source_file, time() - 60);

        set_error_handler(function (int $level, string $message): bool {
            $this->raised_errors[] = $message;

            return true;
        });
    }

    protected function tearDown(): void
    {
        restore_error_handler();
        array_map('unlink', glob($this->folder . '/*'));
        rmdir($this->folder);
    }

    public function test_get_by_file_returns_the_cached_value(): void
    {
        $this->cache->set('key', ['a' => 1]);

        $this->assertSame(['a' => 1], $this->cache->get_by_file('key', $this->source_file));
        $this->assertSame([], $this->raised_errors);
    }

    public function test_get_by_file_returns_a_cached_false(): void
    {
        $this->cache->set('key', false);

        $this->assertFalse($this->cache->get_by_file('key', $this->source_file));
        $this->assertSame([], $this->raised_errors);
    }

    #[DataProvider('unusable_cache_contents')]
    public function test_get_by_file_treats_an_unusable_cache_file_as_a_miss(string $contents): void
    {
        file_put_contents($this->folder . '/key' . File::FILE_EXTENSION, $contents);

        $this->assertNull($this->cache->get_by_file('key', $this->source_file));
        $this->assertSame([], $this->raised_errors);
    }

    public static function unusable_cache_contents(): array
    {
        return [
            'truncated' => [substr(serialize(['expiration' => 0, 'value' => 'value']), 0, 20)],
            'not serialized' => ['corrupt'],
            'serialized false' => [serialize(false)],
            'not a cache item' => [serialize('value')],
            'empty' => [''],
        ];
    }
}
