<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\WindowsUpdatesTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\WindowsUpdatesTable Test Case
 */
class WindowsUpdatesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\WindowsUpdatesTable
     */
    protected $WindowsUpdates;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
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
        $config = $this->getTableLocator()->exists('WindowsUpdates') ? [] : ['className' => WindowsUpdatesTable::class];
        $this->WindowsUpdates = $this->getTableLocator()->get('WindowsUpdates', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->WindowsUpdates);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @link \App\Model\Table\WindowsUpdatesTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
