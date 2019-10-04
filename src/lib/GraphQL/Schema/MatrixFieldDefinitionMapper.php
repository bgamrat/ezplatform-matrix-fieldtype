<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace EzSystems\EzPlatformMatrixFieldtype\GraphQL\Schema;

use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\Values\ContentType\ContentType;
use eZ\Publish\API\Repository\Values\ContentType\FieldDefinition;
use EzSystems\EzPlatformGraphQL\Schema\Domain\Content\Mapper\FieldDefinition\DecoratingFieldDefinitionMapper;
use EzSystems\EzPlatformGraphQL\Schema\Domain\Content\Mapper\FieldDefinition\FieldDefinitionMapper;

class MatrixFieldDefinitionMapper extends DecoratingFieldDefinitionMapper implements FieldDefinitionMapper
{
    /**
     * @var \EzSystems\EzPlatformMatrixFieldtype\GraphQL\Schema\NameHelper
     */
    private $nameHelper;
    /**
     * @var \eZ\Publish\API\Repository\ContentTypeService
     */
    private $contentTypeService;

    public function __construct(FieldDefinitionMapper $innerMapper, NameHelper $nameHelper, ContentTypeService $contentTypeService)
    {
        parent::__construct($innerMapper);
        $this->nameHelper = $nameHelper;
        $this->contentTypeService = $contentTypeService;
    }

    protected function getFieldTypeIdentifier(): string
    {
        return 'ezmatrix';
    }

    public function mapToFieldValueType(FieldDefinition $fieldDefinition): ?string
    {
        if (!$this->canMap($fieldDefinition)) {
            return parent::mapToFieldValueType($fieldDefinition);
        }

        $contentType = $this->findContentTypeOf($fieldDefinition);
        return sprintf('[%s]', $this->nameHelper->matrixFieldDefinitionType($contentType, $fieldDefinition));
    }

    public function mapToFieldValueResolver(FieldDefinition $fieldDefinition): ?string
    {
        if (!$this->canMap($fieldDefinition)) {
            return parent::mapToFieldValueResolver($fieldDefinition);
        }

        return sprintf(
            '@=resolver("DomainFieldValue", [value, "%s"]).value.getRows()',
            $fieldDefinition->identifier
        );
    }

    private function findContentTypeOf(FieldDefinition $fieldDefinition): ContentType
    {
        foreach ($this->contentTypeService->loadContentTypeGroups() as $group) {
            foreach ($this->contentTypeService->loadContentTypes($group) as $type) {
                $foundFieldDefinition = $type->getFieldDefinition($fieldDefinition->identifier);
                if ($foundFieldDefinition === null) {
                    continue;
                }
                if ($foundFieldDefinition->id === $fieldDefinition->id) {
                    return $type;
                }
            }
        }

        throw new \Exception("Could not find content type for field definition");
    }
}