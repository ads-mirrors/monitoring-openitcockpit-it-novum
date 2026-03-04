<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\MacosAppsHostsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\MacosAppsHostsTable Test Case
 */
class MacosAppsHostsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\MacosAppsHostsTable
     */
    protected $MacosAppsHosts;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.MacosAppsHosts',
        'app.MacosApps',
        'app.Hosts',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('MacosAppsHosts') ? [] : ['className' => MacosAppsHostsTable::class];
        $this->MacosAppsHosts = $this->getTableLocator()->get('MacosAppsHosts', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->MacosAppsHosts);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @link \App\Model\Table\MacosAppsHostsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @link \App\Model\Table\MacosAppsHostsTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
