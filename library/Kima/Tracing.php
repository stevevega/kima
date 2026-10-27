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

}
