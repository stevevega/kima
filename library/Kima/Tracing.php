<?php
/**
 * Kima Tracing
 */
namespace Kima;

use DDTrace\GlobalTracer;

/**
 * Access to the Datadog trace of the current request
 */
class Tracing
{

    /**
     * Gets the root span, or null when there is no root scope
     * ddtrace ext 1.x has none with DD_TRACE_ENABLED=false, where 0.x returned a NoopScope
     * @return \DDTrace\Contracts\Span|null
     */
    public static function get_root_span()
    {
        return GlobalTracer::get()->getRootScope()?->getSpan();
    }

    /**
     * Closes the root scope, which finishes its span only when userland opened it
     * Finishing the extension's own root span makes ddtrace log "There is no user-span on the top of the stack"
     */
    public static function close_root_scope(): void
    {
        GlobalTracer::get()->getRootScope()?->close();
    }

}
