<?php

namespace Drupal\openhour\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\openhour\Entity\projectInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class projectController.
 *
 *  Returns responses for Project routes.
 */
class projectController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->dateFormatter = $container->get('date.formatter');
    $instance->renderer = $container->get('renderer');
    return $instance;
  }

  /**
   * Displays a Project revision.
   *
   * @param int $project_revision
   *   The Project revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($project_revision) {
    $project = $this->entityTypeManager()->getStorage('project')
      ->loadRevision($project_revision);
    $view_builder = $this->entityTypeManager()->getViewBuilder('project');

    return $view_builder->view($project);
  }

  /**
   * Page title callback for a Project revision.
   *
   * @param int $project_revision
   *   The Project revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($project_revision) {
    $project = $this->entityTypeManager()->getStorage('project')
      ->loadRevision($project_revision);
    return $this->t('Revision of %title from %date', [
      '%title' => $project->label(),
      '%date' => $this->dateFormatter->format($project->getRevisionCreationTime()),
    ]);
  }

  /**
   * Generates an overview table of older revisions of a Project.
   *
   * @param \Drupal\openhour\Entity\projectInterface $project
   *   A Project object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(projectInterface $project) {
    $account = $this->currentUser();
    $project_storage = $this->entityTypeManager()->getStorage('project');

    $build['#title'] = $this->t('Revisions for %title', ['%title' => $project->label()]);

    $header = [$this->t('Revision'), $this->t('Operations')];
    $revert_permission = (($account->hasPermission("revert all project revisions") || $account->hasPermission('administer project entities')));
    $delete_permission = (($account->hasPermission("delete all project revisions") || $account->hasPermission('administer project entities')));

    $rows = [];

    $vids = $project_storage->revisionIds($project);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\openhour\Entity\projectInterface $revision */
      $revision = $project_storage->loadRevision($vid);
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $project->getRevisionId()) {
          $link = Link::fromTextAndUrl($date, new Url('entity.project.revision', [
            'project' => $project->id(),
            'project_revision' => $vid,
          ]))->toString();
        }
        else {
          $link = $project->toLink($date)->toString();
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => $this->renderer->renderPlain($username),
              'message' => [
                '#markup' => $revision->getRevisionLogMessage(),
                '#allowed_tags' => Xss::getHtmlTagList(),
              ],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => Url::fromRoute('entity.project.revision_revert', [
                'project' => $project->id(),
                'project_revision' => $vid,
              ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.project.revision_delete', [
                'project' => $project->id(),
                'project_revision' => $vid,
              ]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
    }

    $build['project_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
