import { computed, reactive } from 'vue';
import { GraduationCap, Sigma, Target, UsersRound } from '@lucide/vue';

export function useStats({ locale, showError }) {
    const overview = reactive({
        stats: {
            users: 0,
            admins: 0,
            lecturers: 0,
            students: 0,
            formulas: 0,
            paths: 0,
            levels: 0,
        },
        recent_users: [],
        recent_formulas: [],
    });

    const topKpis = computed(() => [
        {
            title: 'Users',
            value: overview.stats.users,
            hint: `${overview.stats.admins} admin • ${overview.stats.lecturers} lecturer`,
            tone: 'from-indigo-500/20 to-indigo-500/5 border-indigo-400/20',
            icon: UsersRound,
        },
        {
            title: 'Formulas',
            value: overview.stats.formulas,
            hint: `${overview.stats.paths} paths • ${overview.stats.levels} levels`,
            tone: 'from-blue-500/20 to-blue-500/5 border-blue-400/20',
            icon: Sigma,
        },
        {
            title: 'Student Ratio',
            value:
                overview.stats.users > 0
                    ? `${Math.round((overview.stats.students / overview.stats.users) * 100)}%`
                    : '0%',
            hint: `${overview.stats.students} students`,
            tone: 'from-amber-500/20 to-amber-500/5 border-amber-400/20',
            icon: GraduationCap,
        },
        {
            title: 'Health',
            value:
                overview.stats.formulas > 0
                    ? `${Math.min(100, Math.round((overview.stats.formulas / Math.max(1, overview.stats.levels)) * 10))}%`
                    : '0%',
            hint: 'Content coverage',
            tone: 'from-violet-500/20 to-violet-500/5 border-violet-400/20',
            icon: Target,
        },
    ]);

    async function loadOverview() {
        try {
            const { data } = await window.axios.get('/cms-api/overview', {
                params: { locale: locale.value },
            });

            overview.stats = {
                ...overview.stats,
                ...(data?.stats ?? {}),
            };
            overview.recent_users = data?.recent_users ?? [];
            overview.recent_formulas = data?.recent_formulas ?? [];
        } catch (error) {
            showError(error, 'Failed to load overview data.');
        }
    }

    return {
        overview,
        topKpis,
        loadOverview,
    };
}
