from django.shortcuts import render

def upload_view(request):
    if request.method == "POST":
        file = request.FILES.get("file")
        
        context = {"message": "File uploaded successfully!"}
        return render(request, "upload/upload.html", context)

    return render(request, "upload/upload.html")

