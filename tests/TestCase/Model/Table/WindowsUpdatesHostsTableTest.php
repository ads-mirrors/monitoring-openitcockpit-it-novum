<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\WindowsUpdatesHostsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\WindowsUpdatesHostsTable Test Case
 */
class WindowsUpdatesHostsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\WindowsUpdatesHostsTable
     */
    protected $WindowsUpdatesHosts;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.WindowsUpdatesHosts',
        'app.WindowsUpdates',
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
        $config = $this->getTableLocator()->exists('WindowsUpdatesHosts') ? [] : ['className' => WindowsUpdatesHostsTable::class];
        $this->WindowsUpdatesHosts = $this->getTableLocator()->get('WindowsUpdatesHosts', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->WindowsUpdatesHosts);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @link \App\Model\Table\WindowsUpdatesHostsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @link \App\Model\Table\WindowsUpdatesHostsTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
