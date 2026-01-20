<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\WindowsAppsHostsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\WindowsAppsHostsTable Test Case
 */
class WindowsAppsHostsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\WindowsAppsHostsTable
     */
    protected $WindowsAppsHosts;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.WindowsAppsHosts',
        'app.WindowsApps',
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
        $config = $this->getTableLocator()->exists('WindowsAppsHosts') ? [] : ['className' => WindowsAppsHostsTable::class];
        $this->WindowsAppsHosts = $this->getTableLocator()->get('WindowsAppsHosts', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->WindowsAppsHosts);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @link \App\Model\Table\WindowsAppsHostsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @link \App\Model\Table\WindowsAppsHostsTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
