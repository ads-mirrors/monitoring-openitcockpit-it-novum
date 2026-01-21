<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\WindowsAppsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\WindowsAppsTable Test Case
 */
class WindowsAppsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\WindowsAppsTable
     */
    protected $WindowsApps;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
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
        $config = $this->getTableLocator()->exists('WindowsApps') ? [] : ['className' => WindowsAppsTable::class];
        $this->WindowsApps = $this->getTableLocator()->get('WindowsApps', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->WindowsApps);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @link \App\Model\Table\WindowsAppsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
