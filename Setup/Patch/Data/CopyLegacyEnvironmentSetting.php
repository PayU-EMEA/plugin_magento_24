<?php

namespace PayU\PaymentGateway\Setup\Patch\Data;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class CopyLegacyEnvironmentSetting implements DataPatchInterface
{
    private const PATH = 'payment/payu/environment';
    private const LEGACY_PATHS = [
        'payment/payu_gateway/environment',
        'payment/payu_gateway_card/environment',
    ];

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(ModuleDataSetupInterface $moduleDataSetup, ScopeConfigInterface $scopeConfig)
    {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->scopeConfig = $scopeConfig;
    }

    public function apply(): self
    {
        $connection = $this->moduleDataSetup->getConnection();
        $configTable = $this->moduleDataSetup->getTable('core_config_data');

        $scopes = $connection->fetchAll(
            $connection->select()
                ->distinct()
                ->from($configTable, ['scope', 'scope_id'])
                ->where('path IN (?)', self::LEGACY_PATHS)
        );

        $inserts = [];
        foreach ($scopes as $row) {
            $scope = $row['scope'];
            $scopeId = (int)$row['scope_id'];
            if ($this->scopeConfig->getValue(self::PATH, $scope, $scopeId) !== null) {
                continue;
            }
            foreach (self::LEGACY_PATHS as $legacyPath) {
                $value = $this->scopeConfig->getValue($legacyPath, $scope, $scopeId);
                if ($value !== null) {
                    $inserts[] = [
                        'scope' => $scope,
                        'scope_id' => $scopeId,
                        'path' => self::PATH,
                        'value' => $value,
                    ];
                    break;
                }
            }
        }

        if ($inserts) {
            $connection->insertOnDuplicate($configTable, $inserts, ['value']);
        }

        return $this;
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
