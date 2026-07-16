<?php

namespace exAuth\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class GroupFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $userId = session()->get('auth_user_id');

        if ($userId === null || session()->get('auth_logged_in') !== true) {
            return redirect()->to('/login');
        }

        if ($arguments === null || $arguments === []) {
            return null;
        }

        $userGroups = $this->getUserGroups((int) $userId);

        if (array_intersect($arguments, $userGroups) === []) {
            return service('response')->setStatusCode(403)->setJSON([
                'error' => 'Insufficient group permissions',
            ]);
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void
    {
    }

    protected function getUserGroups(int $userId): array
    {
        $cacheKey = "exauth_user_groups_{$userId}";

        $cached = cache()->get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        $rows = db_connect()->table('auth_groups_users')
            ->select('group_id')
            ->where('user_id', $userId)
            ->get()
            ->getResultArray();

        $groups = array_column($rows, 'group_id');

        cache()->save($cacheKey, $groups, 300);

        return $groups;
    }

    public static function invalidate(int $userId): void
    {
        cache()->delete("exauth_user_groups_{$userId}");
    }
}
