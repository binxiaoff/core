<?php

declare(strict_types=1);

namespace KLS\Test\CreditGuaranty\FEI\Unit\Traits;

use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use KLS\Core\Entity\CompanyGroup;
use KLS\Core\Entity\CompanyGroupTag;
use KLS\Core\Entity\Embeddable\Money;
use KLS\CreditGuaranty\FEI\Entity\Program;
use KLS\CreditGuaranty\FEI\Entity\ReportingTemplate;
use KLS\CreditGuaranty\FEI\Entity\ReportingTemplateField;
use KLS\Test\Core\Unit\Traits\PropertyValueTrait;
use KLS\Test\Core\Unit\Traits\UserStaffTrait;
use ReflectionException;

trait ReportingTemplateTrait
{
    use PropertyValueTrait;
    use UserStaffTrait;
    use FieldTrait;

    /**
     * @throws Exception
     */
    protected function createReportingTemplate(string $name): ReportingTemplate
    {
        $program = new Program(
            'Program',
            new CompanyGroupTag(new CompanyGroup('Company Group'), 'code'),
            new Money('EUR', '42'),
            $this->createStaff()
        );

        return new ReportingTemplate($program, $name, $this->createStaff());
    }

    /**
     * @throws ReflectionException
     */
    protected function withMultipleReportingTemplateFields(ReportingTemplate $reportingTemplate): void
    {
        $reportingTemplateFields = new ArrayCollection();

        foreach ($this->createMultipleFields() as $key => $field) {
            $reportingTemplateField = new ReportingTemplateField($reportingTemplate, $field);
            $reportingTemplateField->setPosition($key);
            $reportingTemplateFields->add($reportingTemplateField);
        }

        $this->forcePropertyValue($reportingTemplate, 'reportingTemplateFields', $reportingTemplateFields);
    }
}
