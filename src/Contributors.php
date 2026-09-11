<?php

declare(strict_types=1);

namespace Bpmore\SiteWeather;

use Bpmore\SiteWeather\Contracts\WeatherContributor;
use Illuminate\Contracts\Container\Container;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Finds the contributors other addons have tagged into the container, and
 * asks each for its reading without letting any one of them take the
 * dashboard down.
 *
 * Nothing is cached. Discovery is a walk over a tag and readings are meant
 * to be cheap (see the WeatherContributor contract); a cache here would put a
 * second "as of" time next to each band's own computed_at.
 */
final class Contributors
{
    public function __construct(
        private Container $container,
        private LoggerInterface $logger,
    ) {}

    /**
     * Every usable contributor, keyed by its key(). Tagged classes that do not
     * implement the interface, or that reuse a key, are skipped and logged.
     *
     * Tagged services resolve inside a generator, so a contributor whose
     * constructor throws ends discovery: what was collected before it is kept,
     * the failure is logged, and anything tagged after it is not seen until
     * the broken one is fixed. That is a bug in the product that tagged it,
     * and the log says which.
     *
     * @return array<string, WeatherContributor>
     */
    public function all(): array
    {
        $contributors = [];

        try {
            foreach ($this->container->tagged(WeatherContributor::TAG) as $candidate) {
                if (! $candidate instanceof WeatherContributor) {
                    $this->logger->warning(sprintf(
                        'Site Weather ignored %s: tagged "%s" but does not implement %s.',
                        get_debug_type($candidate),
                        WeatherContributor::TAG,
                        WeatherContributor::class,
                    ));

                    continue;
                }

                $key = $candidate->key();

                if (trim($key) === '') {
                    $this->logger->warning(sprintf(
                        'Site Weather ignored %s: its key() is empty.',
                        $candidate::class,
                    ));

                    continue;
                }

                if (isset($contributors[$key])) {
                    $this->logger->warning(sprintf(
                        'Site Weather ignored %s for band "%s": already provided by %s.',
                        $candidate::class,
                        $key,
                        $contributors[$key]::class,
                    ));

                    continue;
                }

                $contributors[$key] = $candidate;
            }
        } catch (Throwable $e) {
            $this->logger->error(sprintf(
                'Site Weather stopped discovering contributors: a tagged "%s" service could not be resolved. %d found before it. %s',
                WeatherContributor::TAG,
                count($contributors),
                $e->getMessage(),
            ), ['exception' => $e]);
        }

        return $contributors;
    }

    /**
     * A band for every contributor, in registration order. A reading that
     * throws becomes unknown with the headline "Failed to report", logged with
     * the exception; the other bands are unaffected.
     *
     * @return list<Band>
     */
    public function bands(): array
    {
        $bands = [];

        foreach ($this->all() as $key => $contributor) {
            $bands[] = new Band($key, $contributor->label(), $this->readingFrom($contributor));
        }

        return $bands;
    }

    private function readingFrom(WeatherContributor $contributor): Reading
    {
        try {
            return $contributor->reading();
        } catch (Throwable $e) {
            $this->logger->error(sprintf(
                'Site Weather band "%s" (%s) failed to report: %s',
                $contributor->key(),
                $contributor::class,
                $e->getMessage(),
            ), ['exception' => $e]);

            return Reading::unknown('Failed to report');
        }
    }
}
