<?php

namespace OP\ContentReview\Extensions;

use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\ListboxField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Security\Group;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;

/**
 * This extensions add a default schema for new pages and pages without a content
 * review setting.
 *
 * @property int $ReviewPeriodDays
 */
class ContentReviewDefaultSettings extends DataExtension
{
    /**
     * @config
     *
     * @var array
     */
    private static $db = array(
        'ReviewPeriodDays' => 'Int',
        'ReviewFrom' => 'Varchar(255)',
        'ReviewSubject' => 'Varchar(255)',
        'ReviewBody' => 'HTMLText',
    );
    /**
     * @config
     *
     * @var array
     */
    private static $defaults = array(
        'ReviewSubject' => 'Page(s) are due for content review',
        'ReviewBody' => '<h2>Page(s) due for review</h2>'
        . '<p>There are $PagesCount pages that are due for review today by you.</p>',
    );
    /**
     * @config
     *
     * @var array
     */
    private static $many_many = [
        'ContentReviewGroups' => Group::class,
        'ContentReviewUsers' => Member::class,
    ];
    public function updateCMSFields(FieldList $fields)
    {
        $helpText = LiteralField::create(
            'ContentReviewHelp',
            _t(
                __CLASS__ . '.DEFAULTSETTINGSHELP',
                'Here we can assign default users and groups'
            )
        );
        $fields->addFieldToTab('Root.ContentReview', $helpText);
        $users = Permission::get_members_by_permission([
            'CMS_ACCESS_CMSMain',
            'ADMIN',
        ]);
        $usersMap = $users->map('ID', 'Title')->toArray();
        asort($usersMap);
        $userField = ListboxField::create('OwnerUsers', _t(__CLASS__ . '.PAGEOWNERUSERS', 'Users'), $usersMap)
            ->setAttribute('data-placeholder', _t(__CLASS__ . '.ADDUSERS', 'Add users'))
            ->setDescription(_t(__CLASS__ . '.OWNERUSERSDESCRIPTION', 'Page owners that are responsible for reviews'));
        $fields->addFieldToTab('Root.ContentReview', $userField);
        $groupsMap = [];
        foreach (Group::get() as $group) {
            // Listboxfield values are escaped, use ASCII char instead of &raquo;
            $groupsMap[$group->ID] = $group->getBreadcrumbs(' > ');
        }
        asort($groupsMap);
        $groupField = ListboxField::create('OwnerGroups', _t(__CLASS__ . '.PAGEOWNERGROUPS', 'Groups'), $groupsMap)
            ->setAttribute('data-placeholder', _t(__CLASS__ . '.ADDGROUP', 'Add groups'))
            ->setDescription(_t(__CLASS__ . '.OWNERGROUPSDESCRIPTION', 'Page owners that are responsible for reviews'));
        $fields->addFieldToTab('Root.ContentReview', $groupField);
        // Email content
        $fields->addFieldsToTab(
            'Root.ContentReview',
            [
                TextField::create('ReviewFrom', _t(__CLASS__ . '.EMAILFROM', 'From email address'))
                    ->setDescription(_t(__CLASS__ . '.EMAILFROM_RIGHTTITLE', 'e.g: do-not-reply@site.com')),
                TextField::create('ReviewSubject', _t(__CLASS__ . '.EMAILSUBJECT', 'Subject line')),
                TextAreaField::create('ReviewBody', _t(__CLASS__ . '.EMAILTEMPLATE', 'Email template')),
                LiteralField::create(
                    'TemplateHelp',
                    $this->owner->renderWith('OP\\ContentReview\\ContentReviewAdminHelp')
                ),
            ]
        );
    }
}
