<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\MacosUpdatesTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\MacosUpdatesTable Test Case
 */
class MacosUpdatesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\MacosUpdatesTable
     */
    protected $MacosUpdates;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.MacosUpdates',
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
        $config = $this->getTableLocator()->exists('MacosUpdates') ? [] : ['className' => MacosUpdatesTable::class];
        $this->MacosUpdates = $this->getTableLocator()->get('MacosUpdates', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->MacosUpdates);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @link \App\Model\Table\MacosUpdatesTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @link \App\Model\Table\MacosUpdatesTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
