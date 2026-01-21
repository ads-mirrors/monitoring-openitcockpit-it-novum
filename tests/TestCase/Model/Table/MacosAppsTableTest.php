<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\MacosAppsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\MacosAppsTable Test Case
 */
class MacosAppsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\MacosAppsTable
     */
    protected $MacosApps;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
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
        $config = $this->getTableLocator()->exists('MacosApps') ? [] : ['className' => MacosAppsTable::class];
        $this->MacosApps = $this->getTableLocator()->get('MacosApps', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->MacosApps);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @link \App\Model\Table\MacosAppsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
