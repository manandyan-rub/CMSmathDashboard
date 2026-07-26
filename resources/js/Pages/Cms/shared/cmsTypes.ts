import type { Component } from 'vue';

export interface Pagination {
    page: number;
    last_page: number;
    per_page?: number;
    total: number;
    from?: number | null;
    to?: number | null;
}

export interface CmsUser {
    id: number;
    name: string;
    email: string;
    role: string;
    status: string;
    locale: string;
    permissions: string[];
    country?: { id: number; name: string; iso_code: string } | null;
}

export interface CmsMeta {
    user: CmsUser;
    locales: string[];
    source_locale: string;
    countries: Array<{ id: number; name: string; iso_code: string }>;
    permissions: Array<{ id: number; key: string; label: string; group: string }>;
    resources: Array<{
        key: string;
        title: string;
        view_permission: string;
        manage_permission: string;
    }>;
}

export interface NavigationItem {
    key: string;
    labelKey: string;
    icon: Component;
    permission: string;
    resource?: string;
}

export interface NavigationGroup {
    key: string;
    labelKey: string;
    items: NavigationItem[];
}

export interface ResourceField {
    key: string;
    label: string;
    type: string;
    required: boolean;
    options?: Array<{ value: string | number; label: string }>;
}

export interface ResourceSchema {
    key: string;
    title: string;
    fields: ResourceField[];
    translation_fields: Array<{ key: string; label: string }>;
    locales: string[];
    sorts: string[];
    permissions: { view: string; manage: string };
}
