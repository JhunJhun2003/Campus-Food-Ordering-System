<!-- TAB 3: ACCESS CONTROL -->
<div id="tab-access-control" class="tab-content <?php echo $activeTab === 'access-control' ? '' : 'hidden'; ?>">
    <div class="bg-white border border-slate-100 rounded-xl shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-slate-900 flex items-center space-x-2">
                    <i class="fa-solid fa-users text-indigo-500"></i>
                    <span>Roles & Permissions</span>
                </h2>
                <p class="text-sm text-slate-500">Manage user roles and their permissions</p>
            </div>
            <button onclick="openCreateRoleModal()" 
                    class="flex items-center space-x-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold transition-colors">
                <i class="fa-solid fa-plus"></i>
                <span>Create Role</span>
            </button>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($roles as $role): ?>
                    <div class="bg-white border border-slate-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <span class="font-semibold text-slate-900"><?php echo htmlspecialchars(ucfirst($role['name'])); ?></span>
                                <?php if ($role['id'] === 1): ?>
                                    <span class="inline-block px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-xs font-semibold ml-2">
                                        <i class="fa-solid fa-crown mr-1"></i> Full Access
                                    </span>
                                <?php else: ?>
                                    <span class="text-xs text-slate-500"><?php echo count($role['permissions']); ?> permissions</span>
                                <?php endif; ?>
                            </div>
                            <div class="flex space-x-1">
                                <?php if ($role['id'] !== 1): ?>
                                    <button onclick="editRole(<?php echo $role['id']; ?>)" 
                                            class="p-1.5 text-slate-400 hover:text-indigo-600 rounded transition-colors" title="Edit Role">
                                        <i class="fa-solid fa-pen text-xs"></i>
                                    </button>
                                <?php endif; ?>
                                <?php if (!in_array($role['id'], [1, 2, 3])): ?>
                                    <button onclick="deleteRole(<?php echo $role['id']; ?>, '<?php echo htmlspecialchars($role['name']); ?>')" 
                                            class="p-1.5 text-slate-400 hover:text-red-600 rounded transition-colors" title="Delete Role">
                                        <i class="fa-solid fa-trash text-xs"></i>
                                    </button>
                                <?php endif; ?>
                                <?php if ($role['id'] !== 1): ?>
                                    <button onclick="managePermissions(<?php echo $role['id']; ?>)" 
                                            class="p-1.5 text-slate-400 hover:text-indigo-600 rounded transition-colors" title="Manage Permissions">
                                        <i class="fa-solid fa-key text-xs"></i>
                                    </button>
                                <?php else: ?>
                                    <span class="text-xs text-slate-400 flex items-center px-2">
                                        <i class="fa-solid fa-lock mr-1"></i> Built-in
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-1">
                            <?php if ($role['id'] === 1): ?>
                                <span class="inline-block px-2 py-0.5 bg-emerald-50 text-emerald-600 rounded text-xs">
                                    <i class="fa-solid fa-check-circle mr-1"></i> All permissions granted
                                </span>
                            <?php else: ?>
                                <?php 
                                $displayPermissions = array_slice($role['permissions'], 0, 5);
                                foreach ($displayPermissions as $perm): 
                                ?>
                                    <span class="inline-block px-2 py-0.5 bg-slate-100 text-slate-600 rounded text-xs">
                                        <?php echo htmlspecialchars($perm['display_name'] ?? $perm['name']); ?>
                                    </span>
                                <?php endforeach; ?>
                                <?php if (count($role['permissions']) > 5): ?>
                                    <span class="inline-block px-2 py-0.5 bg-slate-100 text-slate-400 rounded text-xs">
                                        +<?php echo count($role['permissions']) - 5; ?> more
                                    </span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>