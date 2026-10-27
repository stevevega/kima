<?php
namespace Kima\Tests;

use DDTrace\Contracts\Tracer;
use DDTrace\GlobalTracer;
use DDTrace\NoopSpan;
use DDTrace\NoopTracer;
use ErrorException;
use Kima\Error;
use Kima\Prime\App;
use Kima\Tracing;
use PHPUnit\Framework\TestCase;

final class RootScopeTest extends TestCase
{
    protected function setUp(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';

        $tracer = $this->createStub(Tracer::class);
        $tracer->method('getRootScope')->willReturn(null);
        GlobalTracer::set($tracer);
    }

    protected function tearDown(): void
    {
        GlobalTracer::set(NoopTracer::create());
    }

    public function test_get_root_span_is_null_without_a_root_scope(): void
    {
        $this->assertNull(Tracing::get_root_span());
    }

    public function test_get_root_span_returns_the_root_scope_span(): void
    {
        GlobalTracer::set(NoopTracer::create());

        $this->assertInstanceOf(NoopSpan::class, Tracing::get_root_span());
    }

    public function test_app_run_dispatches_the_action(): void
    {
        $this->assertSame('index', App::get_instance()->run(['/' => 'Index']));
    }

    public function test_error_set_raises_the_user_error(): void
    {
        App::get_instance()->set_config();
        set_error_handler(static function (int $level, string $message): bool {
            if (E_USER_ERROR !== $level) {
                return true;
            }
            throw new ErrorException($message, 0, $level);
        });

        try {
            $this->expectException(ErrorException::class);
            $this->expectExceptionMessage('root scope probe');
            Error::set('root scope probe');
        } finally {
            restore_error_handler();
        }
    }
}
