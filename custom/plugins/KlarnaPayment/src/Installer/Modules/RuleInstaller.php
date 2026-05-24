<?php

declare(strict_types=1);

namespace KlarnaPayment\Installer\Modules;

use KlarnaPayment\Installer\InstallerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;

class RuleInstaller implements InstallerInterface
{
    private const RULE_ID = 'f3f95e9b4f7b446799aa22feae0c61aa';

    /** @var EntityRepository */
    private $ruleRepository;

    public function __construct(EntityRepository $ruleRepository)
    {
        $this->ruleRepository = $ruleRepository;
    }

    public function install(InstallContext $context): void
    {
        $this->removeAvailabilityRule($context->getContext());
    }

    public function update(UpdateContext $context): void
    {
        $this->removeAvailabilityRule($context->getContext());
    }

    public function uninstall(UninstallContext $context): void
    {
        $this->removeAvailabilityRule($context->getContext());
    }

    public function activate(ActivateContext $context): void
    {
        // Nothing to do here
    }

    public function deactivate(DeactivateContext $context): void
    {
        // Nothing to do here
    }

    private function removeAvailabilityRule(Context $context): void
    {
        $deletion = [
            'id' => self::RULE_ID,
        ];

        $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($deletion): void {
            $this->ruleRepository->delete([$deletion], $context);
        });
    }
}
