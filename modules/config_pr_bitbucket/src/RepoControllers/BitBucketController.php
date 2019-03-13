<?php

namespace Drupal\config_pr_bitbucket\RepoControllers;

use Drupal\config_pr\RepoControllerInterface;
use Bitbucket\API\Authentication;
use Bitbucket\API\Http\Client;
use Bitbucket\API\Repositories;
use Bitbucket\API;

/**
 * Class to define the BitBucket controller.
 *
 * @see \Drupal\config_pr\RepoControllerInterface
 */
class BitBucketController implements RepoControllerInterface
{

  /**
   * Holds the controller name.
   *
   * @var string $name .
   */
  protected $name = 'BitBucket';

  /**
   * Holds the controller Id.
   *
   * @var string $id .
   */
  protected $id = 'config_pr_bitbucket.repo_controller.bitbucket';

  /**
   * @var $repo_user
   *   The repo user
   */
  private $repo_user;

  /**
   * @var $defaultBranch
   *   The repository default branch.
   */
  private $defaultBranch;

  /**
   * @var $name
   *   The repo repo_name
   */
  private $repo_name;

  /**
   * @var $appPassword
   *   The App password
   */
  private $appPassword = '';

  /**
   * @var $client
   *    The client instance
   */
  private $client;

  /**
   * @var $committer
   *   The committer username and email
   */
  private $committer = [];

  /**
   * {@inheritdoc}
   */
  public function getName()
  {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getOpenPrs()
  {
    $openPullRequests = new Repositories\PullRequests();
    return $openPullRequests->all($this->repo_user, $this->repo_name);
  }

  /**
   * {@inheritdoc}
   */
  public function setCommitter($committer)
  {
    $this->committer = $committer;
  }

  /**
   * {@inheritdoc}
   */
  public function getRepoUser()
  {
    return $this->repo_user;
  }

  /**
   * {@inheritdoc}
   */
  public function getRepoName()
  {
    return $this->repo_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getCommitter()
  {
    return $this->committer;
  }

  /**
   * {@inheritdoc}
   */
  public function setAppPassword($appPassword)
  {
    $this->appPassword = $appPassword;
  }

  /**
   * {@inheritdoc}
   */
  public function getAppPassword()
  {
    return $this->appPassword;
  }

  /**
   * {@inheritdoc}
   */
  public function branchExists($branchName)
  {
    if ($this->findBranch($branchName)) {
      return true;
    }
  }

  /**
   * Checks if a branch exists.
   *
   * @param $branch
   */
  private function findBranch($branchName)
  {
    $references = new References($this->getClient());
    $branches = $this->listBranches($references);
    foreach ($branches as $item) {
      if ($item['ref'] == 'refs/heads/' . $branchName) {
        return $item;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSha($branch)
  {
  }

  /**
   * List branches.
   *
   * @param References $references
   *
   * @return array
   */
  private function listBranches(\Github\Api\GitData\References $references)
  {
    $branches = $references->branches($this->repo_user, $this->repo_name);

    return $branches;
  }

  /**
   * {@inheritdoc}
   */
  public function setRepoUser($repo_user)
  {
    $this->repo_user = $repo_user;
  }

  /**
   * {@inheritdoc}
   */
  public function setRepoName($repo_name)
  {
    $this->repo_name = $repo_name;
  }

  /**
   * Creates a new branch from the default branch.
   *
   * @param $branchName
   *
   * @return array
   */
  public function createBranch($branchName)
  {
    $references = new References($this->getClient());
    $defaultBranch = $this->getDefaultBranch();

    if ($sha = $this->getSha($defaultBranch)) {
      $params = [
        'ref' => 'refs/heads/' . $branchName,
        'sha' => $sha,
      ];

      if ($this->branchExists($branchName)) {
        return false;
      }

      $branch = $references->create($this->repo_user, $this->repo_name, $params);

      return $branch;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate()
  {
    $user = new API\User();
    $user->setCredentials(new Authentication\Basic($this->getRepoUser(), $this->getAppPassword()));
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultBranch()
  {
    $defaultBranch = new Repositories\Repository();
    $defaultBranch->branch($this->getRepoUser(), $this->getRepoName());
    return $defaultBranch;
  }

  /**
   * {@inheritdoc}
   */
  public function createPr($base, $branch, $title, $body)
  {
    // TODO add catch.
    $params = array(
      'title' => 'Test PR',
      'description' => 'Fixed readme',
      'source' => array(
        'branch' => array(
          'name' => 'config_pr'
        ),
        'repository' => array(
          'full_name' => ''
        )
      ),
      'destination' => array(
        'branch' => array(
          'name' => $this->getDefaultBranch(),
        ),
      ),
    );
    $pullRequest = new Repositories\PullRequests();
    $pullRequest->create($this->getRepoUser(), $this->getRepoName(), $params);

  }

  /**
   * Get the SHA of the file
   *
   * @param $path
   *    The absolute path and file repo_name.
   */
  private function getFileSha($path)
  {

  }


  /**
   * {@inheritdoc}
   */
  public function createFile($path, $content, $commitMessage, $branchName)
  {
  }

  /**
   * {@inheritdoc}
   */
  public function updateFile($path, $content, $commitMessage, $branchName)
  {
  }

  /**
   * {@inheritdoc}
   */
  public function deleteFile($path, $commitMessage, $branchName)
  {
  }

  /**
   * {@inheritdoc}
   */
  public function getClient()
  {
    if (!is_null($this->client)) {
      return $this->client;
    }

    $this->client = new Client();
    $this->authenticate();

    return $this->client;
  }

}
