<?php
namespace Kima\Tests;

use Composer\InstalledVersions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DdtraceRootSpanTest extends TestCase
{
    private const PROBE = __DIR__ . '/../fixture/root_span_probe.php';

    protected function setUp(): void
    {
        if (!extension_loaded('ddtrace')) {
            $this->markTestSkipped('needs the ddtrace extension');
        }

        $extension_version = phpversion('ddtrace');
        $package_version = InstalledVersions::getPrettyVersion('datadog/dd-trace');
        if ((int) $package_version > (int) $extension_version) {
            $this->markTestSkipped(sprintf(
                'ddtrace ext %s with tracing enabled fatals on the datadog/dd-trace %s package',
                $extension_version,
                $package_version
            ));
        }
    }

    public static function probes(): array
    {
        return [
            'App::run()' => ['run', 'action: index'],
            'Error::set()' => ['error', 'Kima Error: root span probe'],
        ];
    }

    #[DataProvider('probes')]
    public function test_closing_the_extension_root_span_logs_no_ddtrace_error(string $probe, string $expected_output): void
    {
        $output = $this->run_probe_with_tracing_enabled($probe);

        $this->assertStringContainsString('root scope: open', $output);
        $this->assertStringContainsString($expected_output, $output);
        $this->assertStringNotContainsString('no user-span', $output);
    }

    private function run_probe_with_tracing_enabled(string $probe): string
    {
        $env = [
            'DD_TRACE_ENABLED' => '1',
            'DD_TRACE_CLI_ENABLED' => '1',
            'DD_TRACE_AGENT_URL' => 'http://127.0.0.1:1',
            'DD_TRACE_SIDECAR_TRACE_SENDER' => '0',
        ] + getenv();
        $process = proc_open(
            [PHP_BINARY, self::PROBE, $probe],
            [1 => ['pipe', 'w'], 2 => ['redirect', 1]],
            $pipes,
            null,
            $env
        );
        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        proc_close($process);

        return $output;
    }
}
