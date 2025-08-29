from django.shortcuts import render
from django.core.files.storage import FileSystemStorage
import os
from django.conf import settings

def files_view(request):
    fs = FileSystemStorage(location=settings.MEDIA_ROOT)
    context = {}

    # Handle file deletion
    if request.method == "POST" and "delete_file" in request.POST:
        file_to_delete = request.POST["delete_file"]
        file_path = os.path.join(settings.MEDIA_ROOT, file_to_delete)
        if os.path.exists(file_path):
            os.remove(file_path)
            context["message"] = f"{file_to_delete} deleted successfully!"

    # List all uploaded files with size
    uploaded_files = []
    for file_name in fs.listdir('')[1]:
        file_path = os.path.join(settings.MEDIA_ROOT, file_name)
        size_kb = round(os.path.getsize(file_path)/1024, 2)
        uploaded_files.append({
            "name": file_name,
            "size": size_kb,
            "url": fs.url(file_name)
        })

    context["uploaded_files"] = uploaded_files
    return render(request, "files/files.html", context)
