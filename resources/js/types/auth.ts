export type Role = {
    id: string;
    name: string;
    label: string;
};

export type User = {
    id: string;
    name: string;
    username: string | null;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    roles: Role[];
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};
