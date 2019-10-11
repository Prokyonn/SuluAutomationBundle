<?php


namespace Sulu\Bundle\AutomationBundle\Metadata;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadataLoaderInterface;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\OptionMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataInterface;
use Sulu\Bundle\AutomationBundle\TaskHandler\AutomationTaskHandlerInterface;
use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Symfony\Component\Translation\TranslatorInterface;
use Task\Handler\TaskHandlerFactoryInterface;

class FormMetadataLoader implements FormMetadataLoaderInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var TaskHandlerFactoryInterface
     */
    private $taskHandlerFactory;

    public function __construct(
        TranslatorInterface $translator,
        TaskHandlerFactoryInterface $taskHandlerFactory
    )
    {
        $this->translator = $translator;
        $this->taskHandlerFactory = $taskHandlerFactory;
    }


    public function getMetadata(string $key, string $locale, array $metadataOptions): ?MetadataInterface
    {
        if (strcmp('task_details', $key) !== 0) {
            return null;
        }

        /** @var FormMetaData $form */
        $form = new FormMetadata();
        if (!$form) {
            return null;
        }

        // Single Select
        $singleSelectHandler = new FieldMetadata('handlerClass');
        $singleSelectHandler->setType('single_select');
        $singleSelectHandler->setLabel($this->translator->trans('sulu_automation.task.name', [], 'admin', $locale));
        $singleSelectHandler->setRequired(true);

        $defaultValueOption = new OptionMetadata();
        $defaultValueOption->setName('default_value');
        $defaultValueOption->setValue('');
        $singleSelectHandler->addOption($defaultValueOption);

        $valuesOption = new OptionMetadata();
        $valuesOption->setName('values');

        foreach ($this->taskHandlerFactory->getHandlers() as $handler) {
            if ($handler instanceof AutomationTaskHandlerInterface
                && isset($metadataOptions['entity-class']) && $handler->supports($metadataOptions['entity-class'])) {
                $configuration = $handler->getConfiguration();

                $handlerOption = new OptionMetadata();
                $handlerOption->setName(get_class($handler));
                $handlerOption->setTitle($configuration->getTitle());

                $valuesOption->addValueOption($handlerOption);
            }
        }
        $singleSelectHandler->addOption($valuesOption);
        $form->addItem($singleSelectHandler);

        // Time Field
        $timeField = new FieldMetadata('time');
        $timeField->setType('time');
        $timeField->setColSpan(6);
        $timeField->setLabel($this->translator->trans('sulu_automation.task.schedule.time', [], 'admin', $locale));
        $form->addItem($timeField);

        // Date Field
        $dateField = new FieldMetadata('date');
        $dateField->setType('date');
        $dateField->setColSpan(6);
        $dateField->setLabel($this->translator->trans('sulu_automation.task.schedule.date', [], 'admin', $locale));

        $displayOption = new OptionMetadata();
        $displayOption->setName('display_options');
        $displayOption->setType('collection');

        $formatOption = new OptionMetadata();
        $formatOption->setName('format');
        $formatOption->setValue('yyyy-mm-dd');
        $displayOption->addValueOption($formatOption);

        $dateField->addOption($displayOption);
        $form->addItem($dateField);

        return $form;
    }
}
