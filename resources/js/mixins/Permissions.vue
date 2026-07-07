<script>
export default {
    methods: {
        $can(permissionName) {
            if (Role === 'Super Admin') {
                return true;
            }
            return UserPermissions.indexOf(permissionName) !== -1;
        },
        $role(roleName) {
            return Role === roleName;
        },
    },
};
</script>
