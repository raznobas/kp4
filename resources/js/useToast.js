import { toast } from "vue3-toastify";
import "vue3-toastify/dist/index.css";

export const useToast = () => {
    const showToast = (message, type = "success", options = {}) => {
        const defaultOptions = {
            theme: "colored",
            type: type,
            ...options,
        };

        toast(message, defaultOptions);
    };

    return {
        showToast,
    };
};
