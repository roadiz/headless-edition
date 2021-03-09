<?php
declare(strict_types=1);

namespace App\TreeWalker;

use App\TreeWalker\Definition\MultiTypeChildrenDefinition;
use RZ\Roadiz\Contracts\NodeType\NodeTypeFieldInterface;
use RZ\Roadiz\Contracts\NodeType\NodeTypeInterface;
use RZ\TreeWalker\AbstractWalker;
use RZ\TreeWalker\Definition\ZeroChildrenDefinition;

/**
 * AutoChildrenNodeSourceWalker automatically creates Walker definitions based on your Node-types
 * children fields default values.
 *
 * @package App\TreeWalker
 */
final class AutoChildrenNodeSourceWalker extends AbstractWalker
{
    protected function initializeDefinitions(): void
    {
        if ($this->isRoot()) {
            $context = $this->getContext();
            if ($context instanceof NodeSourceWalkerContext) {
                /** @var NodeTypeInterface $nodeType */
                foreach ($context->getNodeTypesBag()->all() as $nodeType) {
                    $this->addDefinition(
                        $nodeType->getSourceEntityFullQualifiedClassName(),
                        $this->createDefinitionForNodeType($nodeType)
                    );
                }
            }
        }
    }

    /**
     * @param NodeTypeInterface $nodeType
     * @return callable
     */
    protected function createDefinitionForNodeType(NodeTypeInterface $nodeType): callable
    {
        $childrenFields = $nodeType->getFields()->filter(function (NodeTypeFieldInterface $field) {
            return $field->isChildrenNodes() && null !== $field->getDefaultValues();
        });
        if ($childrenFields->count()) {
            $childrenTypes = [];
            /** @var NodeTypeFieldInterface $field */
            foreach ($childrenFields as $field) {
                $childrenTypes = array_merge($childrenTypes, array_filter(
                    array_map('trim', explode(',', $field->getDefaultValues() ?? ''))
                ));
            }
            if (count($childrenTypes) > 0) {
                return new MultiTypeChildrenDefinition($this->getContext(), array_unique($childrenTypes));
            }
        }

        return new ZeroChildrenDefinition($this->getContext());
    }
}
