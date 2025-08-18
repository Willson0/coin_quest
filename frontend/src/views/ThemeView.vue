<script>
import LessonComponent from "@/components/LessonComponent.vue";

export default {
    name: "ThemeView",
    components: {LessonComponent},
    async mounted () {
        this.id = this.$route.query.id;
        this.initCourse();
    },
    data() {
        return {
            gradients: {
                '1': 'linear-gradient(0deg, transparent 0%, #639AFF 100%)',
                '2': 'linear-gradient(0deg, transparent 0%, #63FFCE 100%)',
                '3': 'linear-gradient(0deg, transparent 0%, #F84040FF 100%)',
            },
            id: 0,
            course: {},
        }
    },
    methods: {
        initCourse() {
            if (!this.user.courses) return;
            if (Object.keys(this.course).length) return;
            this.course = this.user.courses.find(c => c.id === Number(this.id));
        },
        getRussianLessons(count) {
            if (count === 1) return 'урок';
            else if (count > 1 && count < 5) return 'урока'
            else return 'уроков';
        }
    },
    computed: {
        user() {
            return this.$store.state.user;
        },
    },
    watch: {
        user () {
            this.initCourse();
        }
    }
}
</script>

<template>
    <div class="theme">
        <div class="theme_background" :style="{background: gradients[course.level]}"></div>
        <div class="theme_level">{{ user.levels?.[course.level] }}</div>
        <div class="theme_title">
            {{ course.title }}
        </div>
        <div class="theme_description">
            {{ course.description }}
        </div>
        <div class="theme_progress">
            <div class="theme_progress_title">
                <div>Пройдите {{ course.lessons?.length }} {{ getRussianLessons(course.lessons?.length) }}</div>
                <div>{{ Math.round(course.progress / 100 * course.lessons?.length) }} / {{ course.lessons?.length }}</div>
            </div>
            <div class="theme_progress_bar">
                <div :style="{width: course.progress + '%'}" class="theme_progress_bar_fill"></div>
                <div class="theme_progress_bar_locked"></div>
                <div style="position: absolute; right:4px; transform: none" class="profile_progress_bar_item">
                    <svg v-if="course.progress === 100" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 22 22" fill="none">
                        <path d="M5.00002 4.14307H17C17.682 4.14307 18.3361 4.41398 18.8183 4.89622C19.3005 5.37846 19.5714 6.03251 19.5714 6.71449V9.14307C19.5714 9.69535 19.1237 10.1431 18.5714 10.1431H14C13.7633 10.1431 13.5714 9.95119 13.5714 9.7145C13.5714 9.4778 13.3796 9.28592 13.1429 9.28592H8.85716C8.62047 9.28592 8.42859 9.4778 8.42859 9.7145C8.42859 9.95119 8.23671 10.1431 8.00002 10.1431H3.42859C2.8763 10.1431 2.42859 9.69535 2.42859 9.14307V6.71449C2.42859 6.03251 2.69951 5.37846 3.18174 4.89622C3.66398 4.41398 4.31803 4.14307 5.00002 4.14307ZM10.1429 11.0002C10.1429 10.5268 10.5266 10.1431 11 10.1431C11.4734 10.1431 11.8572 10.5268 11.8572 11.0002C11.8572 11.4736 11.4734 11.8574 11 11.8574C10.5266 11.8574 10.1429 11.4736 10.1429 11.0002ZM2.42859 12.0002C2.42859 11.4479 2.8763 11.0002 3.42859 11.0002H7.8225C8.15723 11.0002 8.42859 11.2716 8.42859 11.6063C8.42859 11.767 8.49244 11.9212 8.60611 12.0349L9.84998 13.2787C10.0375 13.4663 10.2919 13.5716 10.5571 13.5716H11.4429C11.7082 13.5716 11.9625 13.4663 12.1501 13.2787L13.3939 12.0349C13.5076 11.9212 13.5714 11.767 13.5714 11.6063C13.5714 11.2716 13.8428 11.0002 14.1775 11.0002H18.5714C19.1237 11.0002 19.5714 11.4479 19.5714 12.0002V16.8574C19.5714 17.4096 19.1237 17.8574 18.5714 17.8574H3.42859C2.8763 17.8574 2.42859 17.4096 2.42859 16.8574V12.0002Z" fill="#FF7700"/>
                    </svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18" fill="none">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M3 5.4C3 3.96783 3.63214 2.59432 4.75736 1.58162C5.88258 0.568927 7.4087 0 9 0C10.5913 0 12.1174 0.568927 13.2426 1.58162C14.3679 2.59432 15 3.96783 15 5.4H16C16.5304 5.4 17.0391 5.58964 17.4142 5.92721C17.7893 6.26477 18 6.72261 18 7.2V16.2C18 16.6774 17.7893 17.1352 17.4142 17.4728C17.0391 17.8104 16.5304 18 16 18H2C1.46957 18 0.960859 17.8104 0.585786 17.4728C0.210714 17.1352 0 16.6774 0 16.2V7.2C0 6.72261 0.210714 6.26477 0.585786 5.92721C0.960859 5.58964 1.46957 5.4 2 5.4H3ZM9 1.8C10.0609 1.8 11.0783 2.17928 11.8284 2.85442C12.5786 3.52955 13 4.44522 13 5.4H5C5 4.44522 5.42143 3.52955 6.17157 2.85442C6.92172 2.17928 7.93913 1.8 9 1.8ZM11 10.8C11 11.116 10.9076 11.4263 10.732 11.7C10.5565 11.9736 10.304 12.2008 10 12.3588V13.5C10 13.7387 9.89464 13.9676 9.70711 14.1364C9.51957 14.3052 9.26522 14.4 9 14.4C8.73478 14.4 8.48043 14.3052 8.29289 14.1364C8.10536 13.9676 8 13.7387 8 13.5V12.3588C7.61874 12.1607 7.32077 11.8549 7.15231 11.4888C6.98384 11.1227 6.95429 10.7168 7.06824 10.3341C7.18219 9.95139 7.43326 9.6132 7.78253 9.37199C8.1318 9.13077 8.55975 9.00002 9 9C9.53043 9 10.0391 9.18964 10.4142 9.52721C10.7893 9.86477 11 10.3226 11 10.8Z" fill="#1E1E22"/>
                    </svg>
                </div>
            </div>
            <div class="theme_progress_title" style="color: var(--inactive)">
                Пройдите экзамен, чтобы получить награду за прохождение темы
            </div>
        </div>
        <div class="theme_lessons">
            <div class="theme_lessons_title">Уроки</div>
            <div class="theme_lessons_main">
                <lesson-component v-for="les in course.lessons" :lesson="les"
                      :is-locked="(les.number !== 1 && ((course.lessons?.find(lesson => lesson.number === les.number-1)?.user_points) == null))"/>
            </div>
        </div>
<!--        <lesson-component class="theme_exam" tries="2"/>-->
    </div>
</template>

<style scoped>

</style>