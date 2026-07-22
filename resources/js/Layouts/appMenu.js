export function createAppMenu(route) {
    return [
        {
            label: 'Overview',
            items: [
                {
                    label: 'Dashboard',
                    icon: 'pi pi-home',
                    href: route('dashboard'),
                    roles: ['super_admin', 'branch_admin', 'receptionist', 'doctor', 'hr', 'accountant'],
                },
            ],
        },
        {
            label: 'Clinic Management',
            items: [
                {
                    label: 'Inquiries',
                    icon: 'pi pi-megaphone',
                    href: route('inquiries.index'),
                    roles: ['super_admin', 'branch_admin', 'receptionist'],
                },
                {
                    label: 'Patients',
                    icon: 'pi pi-users',
                    href: route('patients.index'),
                    roles: ['super_admin', 'branch_admin', 'receptionist', 'doctor'],
                },
                {
                    label: 'Appointments',
                    icon: 'pi pi-calendar',
                    href: route('appointments.index'),
                    roles: ['super_admin', 'branch_admin', 'receptionist', 'doctor'],
                },
                {
                    label: 'Finance',
                    icon: 'pi pi-wallet',
                    href: route('finance.index'),
                    roles: ['super_admin', 'branch_admin', 'accountant', 'hr'],
                },
            ],
        },
        {
            label: 'Masters',
            items: [
                {
                    label: 'Branches',
                    icon: 'pi pi-building',
                    href: route('masters.branches.index'),
                    roles: ['super_admin', 'branch_admin'],
                },
                {
                    label: 'Users',
                    icon: 'pi pi-id-card',
                    href: route('masters.users.index'),
                    roles: ['super_admin', 'branch_admin'],
                },
                {
                    label: 'Roles',
                    icon: 'pi pi-shield',
                    href: route('masters.roles.index'),
                    roles: ['super_admin', 'branch_admin'],
                },
            ],
        },
        {
            label: 'Workspace',
            items: [
                {
                    label: 'Public Booking',
                    icon: 'pi pi-globe',
                    href: route('public.booking'),
                    roles: ['super_admin', 'branch_admin', 'receptionist', 'doctor', 'hr', 'accountant'],
                },
                {
                    label: 'Profile',
                    icon: 'pi pi-user-edit',
                    href: route('profile.edit'),
                    roles: ['super_admin', 'branch_admin', 'receptionist', 'doctor', 'hr', 'accountant'],
                },
            ],
        },
    ];
}
