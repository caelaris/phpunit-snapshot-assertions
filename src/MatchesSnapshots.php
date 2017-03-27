<?php

namespace Spatie\Snapshots;

use PHPUnit\Framework\ExpectationFailedException;
use ReflectionClass;
use Spatie\Snapshots\Drivers\JsonDriver;
use Spatie\Snapshots\Drivers\VarDriver;
use Spatie\Snapshots\Drivers\XmlDriver;

trait MatchesSnapshots
{
    public function assertMatchesSnapshot($actual, Driver $driver = null)
    {
        $snapshot = $this->createSnapshotWithDriver($driver ?? new VarDriver());

        $this->doSnapShotAssertion($snapshot, $actual);
    }

    public function assertMatchesXmlSnapshot($actual)
    {
        $this->assertMatchesSnapshot($actual, new XmlDriver());
    }

    public function assertMatchesJsonSnapshot($actual)
    {
        $this->assertMatchesSnapshot($actual, new JsonDriver());
    }

    /**
     * Determines the snapshot's id. By default, the test case's class and
     * method names are used.
     *
     * @return string
     */
    protected function getSnapshotId(): string
    {
        return (new ReflectionClass($this))->getShortName().'__'.$this->getName();
    }

    /**
     * Determines the directory where snapshots are stored. By default a
     * `__snapshots__` directory is created at the same level as the test
     * class.
     *
     * @return string
     */
    protected function getSnapshotDirectory(): string
    {
        return dirname((new ReflectionClass($this))->getFileName()).
            DIRECTORY_SEPARATOR.
            '__snapshots__';
    }

    /**
     * Determines whether or not the snapshot should be updated instead of
     * matched.
     *
     * Override this method it you want to use a different flag or mechanism
     * than `-d --update-snapshots`.
     *
     * @return bool
     */
    protected function shouldUpdateSnapshots(): bool
    {
        return in_array('--update-snapshots', $_SERVER['argv'], true);
    }

    protected function createSnapshotWithDriver(Driver $driver): Snapshot
    {
        return Snapshot::forTestCase(
            $this->getSnapshotId(),
            $this->getSnapshotDirectory(),
            $driver
        );
    }

    protected function doSnapShotAssertion(Snapshot $snapshot, $actual)
    {
        if (! $snapshot->exists()) {
            $snapshot->create($actual);

            return $this->markTestIncomplete("Snapshot created for {$snapshot->id()}");
        }

        if ($this->shouldUpdateSnapshots()) {
            try {
                // We only want to update snapshots which need updating. If the snapshot doesn't
                // match the expected output, we'll catch the failure, create a new snapshot and
                // mark the test as incomplete.
                $snapshot->assertMatches($actual);
            } catch (ExpectationFailedException $exception) {
                $snapshot->create($actual);

                return $this->markTestIncomplete("Snapshot updated for {$snapshot->id()}");
            }
        }

        $snapshot->assertMatches($actual);
    }
}
