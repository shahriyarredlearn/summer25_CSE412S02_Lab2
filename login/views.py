from django.shortcuts import render

def login_view(request):
    if request.method == "POST":
        username = request.POST.get("username")
        password = request.POST.get("password")
        # এখানে login check করার লজিক বসাতে পারো
        context = {"message": "Login successful!"}
        return render(request, "login/login.html", context)

    return render(request, "login/login.html")
