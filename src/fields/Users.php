<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use Craft;
use craft\db\Table as DbTable;
use craft\elements\db\UserQuery;
use craft\elements\User;
use craft\gql\arguments\elements\User as UserArguments;
use craft\gql\interfaces\elements\User as UserInterface;
use craft\gql\resolvers\elements\User as UserResolver;
use craft\helpers\Db;
use craft\helpers\Gql;
use craft\helpers\Gql as GqlHelper;
use craft\models\GqlSchema;
use craft\services\Gql as GqlService;
use GraphQL\Type\Definition\Type;

/**
 * Users represents a Users field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Users extends BaseRelationField
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Users');
    }

    /**
     * @inheritdoc
     */
    protected static function elementType(): string
    {
        return User::class;
    }

    /**
     * @inheritdoc
     */
    public static function defaultSelectionLabel(): string
    {
        return Craft::t('app', 'Add a user');
    }

    /**
     * @inheritdoc
     */
    public static function valueType(): string
    {
        return UserQuery::class;
    }

    /**
     * @inheritdoc
     */
    public function includeInGqlSchema(GqlSchema $schema): bool
    {
        return Gql::canQueryUsers($schema);
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public function getContentGqlType()
    {
        return [
            'name' => $this->handle,
            'type' => Type::listOf(UserInterface::getType()),
            'args' => UserArguments::getArguments(),
            'resolve' => UserResolver::class . '::resolve',
            'complexity' => GqlHelper::relatedArgumentComplexity(GqlService::GRAPHQL_COMPLEXITY_EAGER_LOAD),
        ];
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public function getEagerLoadingGqlConditions()
    {
        $allowedEntities = Gql::extractAllowedEntitiesFromSchema();
        $userGroupUids = $allowedEntities['usergroups'] ?? [];

        if (in_array('everyone', $userGroupUids, false)) {
            return [];
        }

        if (empty($userGroupUids)) {
            return false;
        }

        $userGroupsService = Craft::$app->getUserGroups();
        $userGroupIds = array_filter(array_map(function(string $uid) use ($userGroupsService) {
            $userGroupsService = $userGroupsService->getGroupByUid($uid);
            return $userGroupsService->id ?? null;
        }, $userGroupUids));

        return [
            'groupId' => $userGroupIds,
        ];
    }
}
