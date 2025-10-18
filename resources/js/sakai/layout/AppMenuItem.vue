<script setup>
import { useLayout } from '@/sakai/layout/composables/layout';
import { onBeforeMount, ref, watch } from 'vue';
import NavLink from "@/Components/NavLink.vue";

const { layoutState, setActiveMenuItem, onMenuToggle } = useLayout();

const props = defineProps({
    item: {
        type: Object,
        default: () => ({})
    },
    index: {
        type: Number,
        default: 0
    },
    root: {
        type: Boolean,
        default: true
    },
    parentItemKey: {
        type: String,
        default: null
    }
});

const isActiveMenu = ref(false);
const itemKey = ref(null);

onBeforeMount(() => {
    itemKey.value = props.parentItemKey ? props.parentItemKey + '-' + props.index : String(props.index);

    const activeItem = layoutState.activeMenuItem;

    isActiveMenu.value = activeItem === itemKey.value || activeItem ? activeItem.startsWith(itemKey.value + '-') : false;
});

watch(
    () => layoutState.activeMenuItem,
    (newVal) => {
        isActiveMenu.value = newVal === itemKey.value || newVal.startsWith(itemKey.value + '-');
    }
);

const itemClick = (event, item) => {
    if (item.disabled) {
        event.preventDefault();
        return;
    }

    if ((item.to || item.url) && (layoutState.staticMenuMobileActive || layoutState.overlayMenuActive)) {
        onMenuToggle();
    }

    if (item.command) {
        item.command({ originalEvent: event, item: item });
    }

    const foundItemKey = item.items ? (isActiveMenu.value ? props.parentItemKey : itemKey) : itemKey.value;

    setActiveMenuItem(foundItemKey);
};
</script>

<template>
    <li :class="{ 
        'layout-root-menuitem': root, 
        'active-menuitem': isActiveMenu,
        'menu-item-with-children': item.items 
    }">
        <!-- Section Header -->
        <div v-if="root && item.label && !item.to" class="menu-section">
            <span class="menu-section-label">{{ item.label }}</span>
        </div>

        <!-- Menu Items -->
        <a v-if="(!item.to || item.items) && item.visible !== false" 
           :href="item.url" 
           @click="itemClick($event, item, index)" 
           :class="['menu-link', item.class, { 'active-route': $page.url === item.url }]"
           :target="item.target" 
           tabindex="0">
            <i :class="item.icon" class="menu-icon"></i>
            <span class="menu-text">{{ item.label }}</span>
            <i class="pi pi-fw pi-angle-down menu-arrow" v-if="item.items"></i>
        </a>

        <NavLink v-if="item.to && !item.items && item.visible !== false" 
                 @click="itemClick($event, item, index)" 
                 :href="item.to"  
                 :class="['menu-link', item.class, { 'active-route': $page.url === item.to }]">
            <i :class="item.icon" class="menu-icon"></i>
            <span class="menu-text">{{ item.label }}</span>
        </NavLink>

        <!-- Submenu -->
        <Transition v-if="item.items && item.visible !== false" name="submenu-slide">
            <ul v-show="root ? true : isActiveMenu" class="layout-submenu">
                <app-menu-item v-show="!child?.can || can([child.can])" 
                              v-for="(child, i) in item.items" 
                              :key="child" 
                              :index="i" 
                              :item="child" 
                              :parentItemKey="itemKey" 
                              :root="false">
                </app-menu-item>
            </ul>
        </Transition>
    </li>
</template>

<style lang="scss" scoped>
.menu-section {
    padding: 1rem 1rem 0.5rem;
    margin-top: 0.5rem;
    
    .menu-section-label {
        color: #64748b;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    &:first-child {
        margin-top: 0;
        padding-top: 0.5rem;
    }
}

.menu-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    color: #475569;
    text-decoration: none;
    border-radius: 6px;
    margin: 0.125rem 0.5rem;
    transition: all 0.2s ease;
    position: relative;
    
    &:hover {
        color: #1e293b;
        background: #f1f5f9;
        
        .menu-icon {
            color: #0f766e;
        }
    }
    
    &.active-route {
        color: #0f766e;
        background: #f0fdfa;
        font-weight: 600;
        
        .menu-icon {
            color: #0f766e;
        }
        
        &::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 60%;
            background: #0f766e;
            border-radius: 0 2px 2px 0;
        }
    }
}

.menu-icon {
    color: #64748b;
    font-size: 1rem;
    width: 20px;
    text-align: center;
    transition: color 0.2s ease;
}

.menu-text {
    flex: 1;
    font-size: 0.875rem;
}

.menu-arrow {
    color: #94a3b8;
    font-size: 0.875rem;
    transition: transform 0.2s ease;
}

// Submenu Styles
.layout-submenu {
    list-style: none;
    margin: 0;
    padding: 0.25rem 0 0.25rem 1.5rem;
    background: #f8fafc;
    
    .menu-link {
        padding: 0.625rem 0.875rem;
        margin: 0.125rem 0.25rem;
        font-size: 0.8125rem;
        
        &.active-route {
            background: #ecfdf5;
            
            &::before {
                height: 50%;
            }
        }
    }
    
    .menu-icon {
        font-size: 0.875rem;
    }
}

// Submenu Animation
.submenu-slide-enter-active,
.submenu-slide-leave-active {
    transition: all 0.2s ease;
    max-height: 500px;
    overflow: hidden;
}

.submenu-slide-enter-from,
.submenu-slide-leave-to {
    max-height: 0;
    opacity: 0;
}

// Active state for menu items with children
.active-menuitem {
    > .menu-link {
        .menu-arrow {
            transform: rotate(180deg);
        }
    }
}

// Responsive adjustments
@media (max-width: 991px) {
    .menu-section {
        padding: 0.75rem 0.875rem 0.5rem;
    }
    
    .menu-link {
        padding: 0.75rem 0.875rem;
        margin: 0.125rem 0.25rem;
    }
    
    .layout-submenu {
        padding-left: 1.25rem;
    }
}
</style>