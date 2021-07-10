<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\behaviors;

use craft\base\Element;
use craft\db\Table;
use craft\helpers\Db;
use DateTime;

/**
 * DraftBehavior is applied to element drafts.
 *
 * @property-read Datetime|null $dateLastMerged The date that the canonical element was last merged into this one
 * @property-read bool $mergingChanges Whether recent changes to the canonical element are being merged into this element
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2.0
 */
class DraftBehavior extends BaseRevisionBehavior
{
    /**
     * @var string The draft name
     */
    public $draftName;

    /**
     * @var string|null The draft notes
     */
    public $draftNotes;

    /**
     * @var bool Whether to track changes in this draft
     */
    public $trackChanges = true;

    /**
     * @var bool Whether the draft should be marked as saved (if unpublished).
     * @since 3.6.6
     */
    public $markAsSaved = true;

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            Element::EVENT_AFTER_PROPAGATE => [$this, 'handleSave'],
            Element::EVENT_AFTER_DELETE => [$this, 'handleDelete'],
        ];
    }

    /**
     * Updates the row in the `drafts` table after the draft element is saved.
     */
    public function handleSave()
    {
        Db::update(Table::DRAFTS, [
            'provisional' => $this->owner->isProvisionalDraft,
            'name' => $this->draftName,
            'notes' => $this->draftNotes,
            'dateLastMerged' => Db::prepareDateForDb($this->owner->dateLastMerged),
            'saved' => $this->markAsSaved,
        ], [
            'id' => $this->owner->draftId,
        ], [], false);
    }

    /**
     * Deletes the row in the `drafts` table after the draft element is deleted.
     */
    public function handleDelete()
    {
        if ($this->owner->hardDelete) {
            Db::delete(Table::DRAFTS, [
                'id' => $this->owner->draftId,
            ]);
        }
    }

    /**
     * Returns the draft’s name.
     *
     * @return string
     * @since 3.3.17
     */
    public function getDraftName(): string
    {
        return $this->draftName;
    }

    /**
     * Returns whether the source element has been saved since the time this draft was
     * created or last merged.
     *
     * @return bool
     * @since 3.4.0
     */
    public function getIsOutdated(): bool
    {
        if ($this->owner->getIsCanonical()) {
            return false;
        }

        $canonical = $this->owner->getCanonical();

        if ($this->owner->dateCreated > $canonical->dateUpdated) {
            return false;
        }

        if (!$this->owner->dateLastMerged) {
            return true;
        }

        return $this->owner->dateLastMerged < $canonical->dateUpdated;
    }
}
